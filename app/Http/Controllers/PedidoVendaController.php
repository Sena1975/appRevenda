<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

use App\Models\PedidoVenda;
use App\Models\ItemVenda;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\FormaPagamento;
use App\Models\PlanoPagamento;
use App\Models\Produto;

use App\Services\EstoqueService;
use App\Services\ContasReceberService;

class PedidoVendaController extends Controller
{
    private EstoqueService $estoque;
    private ContasReceberService $cr;

    public function __construct(EstoqueService $estoque, ContasReceberService $cr)
    {
        $this->estoque = $estoque;
        $this->cr      = $cr;
    }

    /**
     * Lista pedidos
     */
    public function index()
    {
        $pedidos = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome'])
            ->orderByDesc('id')
            ->paginate(10);

        return view('vendas.index', compact('pedidos'));
    }

    /**
     * Formulário de novo pedido
     */
    public function create()
    {
        $clientes     = Cliente::orderBy('nome')->get(['id','nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id','nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id','nome']);
        $produtos     = Produto::orderBy('nome')->get(['id','nome','codfabnumero']);

        return view('vendas.create', compact('clientes','revendedoras','formas','produtos'));
    }

    /**
     * Salva um novo pedido (status PENDENTE) e RESERVA estoque
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id'          => 'required|integer|exists:appcliente,id',
            'revendedora_id'      => 'nullable|integer|exists:apprevendedora,id',
            'forma_pagamento_id'  => 'required|integer|exists:appformapagamento,id',
            'plano_pagamento_id'  => 'required|integer|exists:appplanopagamento,id',
            'codplano'            => 'nullable|string|max:20',
            'data_pedido'         => 'nullable|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string',
            'valor_desconto'      => 'nullable|numeric|min:0',

            'itens'                      => 'required|array|min:1',
            'itens.*.produto_id'         => 'required|integer|exists:appproduto,id',
            'itens.*.codfabnumero'       => 'nullable|string|max:30',
            'itens.*.quantidade'         => 'required|integer|min:1',
            'itens.*.preco_unitario'     => 'required|numeric|min:0',
            'itens.*.pontuacao'          => 'nullable|integer|min:0',
            'itens.*.pontuacao_total'    => 'nullable|integer|min:0',

            'pontuacao'        => 'nullable|integer|min:0',
            'pontuacao_total'  => 'nullable|integer|min:0',
        ],[
            'cliente_id.required'         => 'Selecione um cliente.',
            'forma_pagamento_id.required' => 'Selecione a forma de pagamento.',
            'plano_pagamento_id.required' => 'Selecione o plano de pagamento.',
            'itens.required'              => 'Inclua pelo menos um item.',
        ]);

        DB::beginTransaction();

        try {
            // Calcula totais
            $total = 0.0;
            $pontosTotal = 0;          // soma de (qtd * pontos unit)
            $pontosUnitSomatorio = 0;  // soma dos pontos unitários

            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                $total               += $q * $pu;
                $pontosTotal         += $pto;
                $pontosUnitSomatorio += $ptu;
            }

            $desconto = (float)($request->valor_desconto ?? 0);
            $liquido  = max(0, $total - $desconto);

            // Cria cabeçalho (status PENDENTE)
            $pedido = PedidoVenda::create([
                'cliente_id'         => $request->cliente_id,
                'revendedora_id'     => $request->revendedora_id,
                'data_pedido'        => $request->data_pedido ? Carbon::parse($request->data_pedido) : Carbon::now()->toDateString(),
                'previsao_entrega'   => $request->previsao_entrega ?: null,
                'status'             => 'PENDENTE',
                'forma_pagamento_id' => $request->forma_pagamento_id,
                'plano_pagamento_id' => $request->plano_pagamento_id,
                'codplano'           => $request->codplano, // VARCHAR(20)
                'valor_total'        => $total,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $liquido,
                'pontuacao'          => $pontosUnitSomatorio,
                'pontuacao_total'    => $pontosTotal,
                'observacao'         => $request->observacao,
            ]);

            // Itens
            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                ItemVenda::create([
                    'pedido_id'       => $pedido->id,
                    'produto_id'      => $it['produto_id'],
                    'codfabnumero'    => $it['codfabnumero'] ?? null,
                    'quantidade'      => $q,
                    'preco_unitario'  => $pu,
                    'preco_total'     => $q * $pu,
                    'pontuacao'       => $ptu,
                    'pontuacao_total' => $pto,
                    'reservado'       => 0,
                    'entregue'        => 0,
                ]);
            }

            // RESERVA de estoque (não baixa o gerencial)
            $pedido->load('itens.produto');
            $this->estoque->reservarVenda($pedido);

            // Gera Contas a Receber
            $this->cr->gerarParaPedido($pedido);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido salvo como PENDENTE, estoque reservado e parcelas geradas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao salvar: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Editar pedido
     */
    public function edit($id)
    {
        $pedido = PedidoVenda::with(['itens.produto:id,nome,codfabnumero', 'cliente:id,nome', 'revendedora:id,nome'])
            ->findOrFail($id);

        $clientes     = Cliente::orderBy('nome')->get(['id','nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id','nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id','nome']);
        // Busca planos da forma atual do pedido
        $planos       = PlanoPagamento::where('formapagamento_id', $pedido->forma_pagamento_id)
                            ->orderBy('descricao')
                            ->get(['id','descricao','formapagamento_id','parcelas','prazo1','prazo2','prazo3']);
        $produtos     = Produto::orderBy('nome')->get(['id','nome','codfabnumero']);

        return view('vendas.edit', compact('pedido','clientes','revendedoras','formas','planos','produtos'));
    }

    /**
     * Atualiza pedido (mantém como estiver; se ainda PENDENTE, mantém reserva)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'cliente_id'          => 'required|integer|exists:appcliente,id',
            'revendedora_id'      => 'nullable|integer|exists:apprevendedora,id',
            'forma_pagamento_id'  => 'required|integer|exists:appformapagamento,id',
            'plano_pagamento_id'  => 'required|integer|exists:appplanopagamento,id',
            'codplano'            => 'nullable|string|max:20',
            'data_pedido'         => 'nullable|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string',
            'valor_desconto'      => 'nullable|numeric|min:0',

            'itens'                      => 'required|array|min:1',
            'itens.*.produto_id'         => 'required|integer|exists:appproduto,id',
            'itens.*.codfabnumero'       => 'nullable|string|max:30',
            'itens.*.quantidade'         => 'required|integer|min:1',
            'itens.*.preco_unitario'     => 'required|numeric|min:0',
            'itens.*.pontuacao'          => 'nullable|integer|min:0',
            'itens.*.pontuacao_total'    => 'nullable|integer|min:0',

            'pontuacao'        => 'nullable|integer|min:0',
            'pontuacao_total'  => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $pedido = PedidoVenda::with('itens')->findOrFail($id);

            // Recalcula totais
            $total = 0.0;
            $pontosTotal = 0;
            $pontosUnitSomatorio = 0;

            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                $total               += $q * $pu;
                $pontosTotal         += $pto;
                $pontosUnitSomatorio += $ptu;
            }

            $desconto = (float)($request->valor_desconto ?? 0);
            $liquido  = max(0, $total - $desconto);

            // Atualiza cabeçalho (mantém status atual)
            $pedido->update([
                'cliente_id'         => $request->cliente_id,
                'revendedora_id'     => $request->revendedora_id,
                'data_pedido'        => $request->data_pedido ?: $pedido->data_pedido,
                'previsao_entrega'   => $request->previsao_entrega,
                'status'             => $pedido->status ?? 'PENDENTE',
                'forma_pagamento_id' => $request->forma_pagamento_id,
                'plano_pagamento_id' => $request->plano_pagamento_id,
                'codplano'           => $request->codplano,
                'valor_total'        => $total,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $liquido,
                'pontuacao'          => $pontosUnitSomatorio,
                'pontuacao_total'    => $pontosTotal,
                'observacao'         => $request->observacao,
            ]);

            // Recria itens
            ItemVenda::where('pedido_id', $pedido->id)->delete();
            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                ItemVenda::create([
                    'pedido_id'       => $pedido->id,
                    'produto_id'      => $it['produto_id'],
                    'codfabnumero'    => $it['codfabnumero'] ?? null,
                    'quantidade'      => $q,
                    'preco_unitario'  => $pu,
                    'preco_total'     => $q * $pu,
                    'pontuacao'       => $ptu,
                    'pontuacao_total' => $pto,
                    'reservado'       => 0,
                    'entregue'        => 0,
                ]);
            }

            // Se o pedido ainda for PENDENTE, garantimos que a RESERVA esteja coerente:
            if (strtoupper($pedido->status) === 'PENDENTE') {
                // Remove movimentos PENDENTES antigos deste pedido e reverte reserva antiga
                $old = $pedido->load('itens');
                foreach ($old->itens as $itemOld) {
                    DB::table('appestoque')
                        ->where('produto_id', $itemOld->produto_id)
                        ->update([
                            'reservado'  => DB::raw("GREATEST(COALESCE(reservado,0) - {$itemOld->quantidade}, 0)"),
                            'updated_at' => now(),
                        ]);
                }
                DB::table('appmovestoque')
                    ->where('origem', 'VENDA')
                    ->where('origem_id', $pedido->id)
                    ->where('status', 'PENDENTE')
                    ->delete();

                // Reaplica reserva conforme itens novos
                $pedido->load('itens.produto');
                $this->estoque->reservarVenda($pedido);
            }

            // Recalcula/gera CR (apaga ABERTAS e gera de novo)
            $this->cr->recalcularParaPedido($pedido);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao atualizar: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Confirma pedido: baixa gerencial e libera reserva
     */
    public function confirmar($id)
    {
        $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

        if (!in_array(strtoupper($pedido->status), ['PENDENTE','ABERTO'])) {
            return back()->with('info', 'Este pedido não está pendente para confirmação.');
        }

        DB::beginTransaction();
        try {
            // Status ENTREGUE
            $pedido->status = 'ENTREGUE';
            $pedido->save();

            // Baixa gerencial e libera reserva
            $this->estoque->confirmarSaidaVenda($pedido);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido confirmado, reserva liberada e estoque baixado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Falha ao confirmar pedido: '.$e->getMessage());
        }
    }

    /**
     * Exporta CSV do pedido
     */
    public function exportar($id): StreamedResponse
    {
        $pedido = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome', 'itens.produto:id,nome,codfabnumero'])
            ->findOrFail($id);

        $nomeCliente     = $pedido->cliente->nome ?? '';
        $nomeRevendedora = $pedido->revendedora->nome ?? '';
        $arquivo         = "pedido_{$pedido->id}.csv";

        return response()->streamDownload(function () use ($pedido, $nomeCliente, $nomeRevendedora) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Pedido', $pedido->id]);
            fputcsv($out, ['Data', \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y')]);
            fputcsv($out, ['Cliente', $nomeCliente]);
            fputcsv($out, ['Revendedora', $nomeRevendedora]);
            fputcsv($out, ['Status', $pedido->status]);
            fputcsv($out, ['Valor Total', number_format((float)$pedido->valor_total, 2, ',', '.')]);
            fputcsv($out, ['Valor Desconto', number_format((float)$pedido->valor_desconto, 2, ',', '.')]);
            fputcsv($out, ['Valor Líquido', number_format((float)$pedido->valor_liquido, 2, ',', '.')]);
            fputcsv($out, ['Pontuação (unit soma)', (int)$pedido->pontuacao]);
            fputcsv($out, ['Pontuação Total', (int)$pedido->pontuacao_total]);
            fputcsv($out, ['Plano (ID)', (int)$pedido->plano_pagamento_id]);
            fputcsv($out, ['Código do Plano', (string)($pedido->codplano ?? '')]);
            fputcsv($out, []);

            fputcsv($out, ['#', 'CODFAB', 'Produto', 'Qtd', 'Pontos', 'Pontos Total', 'R$ Unit', 'R$ Total']);
            foreach ($pedido->itens as $i => $item) {
                fputcsv($out, [
                    $i + 1,
                    $item->codfabnumero ?? ($item->produto->codfabnumero ?? ''),
                    $item->produto->nome ?? '',
                    (int)$item->quantidade,
                    (int)$item->pontuacao,
                    (int)$item->pontuacao_total,
                    number_format((float)$item->preco_unitario, 2, ',', '.'),
                    number_format((float)$item->preco_total, 2, ',', '.'),
                ]);
            }

            fclose($out);
        }, $arquivo, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Exclui pedido e limpa reservas e CR abertas
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

            // Se estava PENDENTE, desfaz a reserva usando o service (insere movimentação de estorno)
            if (strtoupper($pedido->status) === 'PENDENTE') {
                $this->estoque->cancelarReservaVenda($pedido);
            }

            // Cancela parcelas ABERTAS do CR
            $this->cr->cancelarAbertasPorPedido($pedido->id);

            // Exclui itens e pedido
            ItemVenda::where('pedido_id', $pedido->id)->delete();
            $pedido->delete();

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido excluído com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('vendas.index')->with('error', 'Erro ao excluir: '.$e->getMessage());
        }
    }
}
