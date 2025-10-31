<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoVenda;
use App\Models\ItemVenda;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\FormaPagamento;
use App\Models\Produto;
use Carbon\Carbon;

class PedidoVendaController extends Controller
{
    /**
     * Lista simples de pedidos
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

        // Produtos ativos (ajuste se sua coluna de status tiver outro nome)
        $produtos = Produto::orderBy('nome')->get(['id','nome','codfabnumero'.'']);

        return view('vendas.create', compact('clientes','revendedoras','formas','produtos'));
    }

    /**
     * Salva o pedido e os itens
     */
    public function store(Request $request)
    {
        // Validação base, respeitando seus nomes de tabelas
        $request->validate([
            'cliente_id'        => 'required|integer|exists:appcliente,id',
            'revendedora_id'    => 'nullable|integer|exists:apprevendedora,id',
            'forma_pagamento_id'=> 'required|integer|exists:appformapagamento,id',
            'plano_pagamento_id' => 'required|integer|exists:appplanopagamento,id',
            'codplano'           => 'nullable|string|max:20',

            'data_pedido'       => 'nullable|date',
            'previsao_entrega'  => 'nullable|date',
            'observacao'        => 'nullable|string',
            'valor_desconto'    => 'nullable|numeric|min:0',

            'itens'                     => 'required|array|min:1',
            'itens.*.produto_id'        => 'required|integer|exists:appproduto,id',
            'itens.*.codfabnumero'      => 'nullable|string|max:30',
            'itens.*.quantidade'        => 'required|numeric|min:0.001',
            'itens.*.preco_unitario'    => 'required|numeric|min:0',
            'itens.*.pontuacao'              => 'nullable|integer|min:0',
            'itens.*.pontuacao_total'        => 'nullable|integer|min:0',

            'pontuacao'        => 'nullable|integer|min:0',
            'pontuacao_total'  => 'nullable|integer|min:0',            
        ],[
            'cliente_id.required' => 'Selecione um cliente.',
            'forma_pagamento_id.required' => 'Selecione a forma de pagamento.',
            'itens.required' => 'Inclua pelo menos um item.',
        ]);

        DB::beginTransaction();

        try {
            // Calcula total bruto a partir dos itens
            $total = 0;
            $pontosTotal = 0;
            $pontosUnitSomatorio = 0;
            foreach ($request->itens as $it) {
                $q  = (int)$it['quantidade'];
                $pu = (float)$it['preco_unitario'];
                $total += $q * $pu;

                $ptu = (int)($it['pontuacao'] ?? 0);           // pontos por unidade
                $pto = (int)($it['pontuacao_total'] ?? $q*$ptu);
                $pontosUnitSomatorio += $ptu;
                $pontosTotal         += $pto;
            }
            $desconto = (float)($request->valor_desconto ?? 0);
            $liquido  = max(0, $total - $desconto);

            // Cria o pedido
            $pedido = PedidoVenda::create([
                'cliente_id'         => $request->cliente_id,
                'revendedora_id'     => $request->revendedora_id,
                'data_pedido'        => now()->toDateString(),
                'previsao_entrega'   => $request->previsao_entrega,
                'status'             => 'PENDENTE',
                'forma_pagamento_id' => $request->forma_pagamento_id,
                'plano_pagamento_id' => $request->plano_pagamento_id,
                'codplano'           => $request->codplano,        // <==
                'valor_total'        => $total,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $liquido,
                'pontuacao'          => $pontosUnitSomatorio,      // soma dos pontos unitários
                'pontuacao_total'    => $pontosTotal,              // soma (qtd * pontos)
                'observacao'         => $request->observacao,
            ]);


            // Cria os itens
            foreach ($request->itens as $it) {
                $q  = (int)$it['quantidade'];
                $pu = (float)$it['preco_unitario'];
                $ptu= (int)($it['pontuacao'] ?? 0);
                $pto= (int)($it['pontuacao_total'] ?? ($q * $ptu));

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

            DB::commit();

            return redirect()->route('vendas.index')->with('success', 'Pedido salvo com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao salvar: '.$e->getMessage())->withInput();
        }
    }

   /**
     * Exporta um pedido (cabeçalho do pedido + itens) em CSV.
     */
    public function exportar($id): StreamedResponse
    {
        $pedido = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome', 'itens.produto:id,nome,codfabnumero'])
            ->findOrFail($id);

        $nomeCliente    = $pedido->cliente->nome ?? '';
        $nomeRevendedora= $pedido->revendedora->nome ?? '';
        $arquivo = "pedido_{$pedido->id}.csv";

        return response()->streamDownload(function () use ($pedido, $nomeCliente, $nomeRevendedora) {
            $out = fopen('php://output', 'w');

            // Cabeçalho do pedido (linhas de metadados)
            fputcsv($out, ['Pedido', $pedido->id]);
            fputcsv($out, ['Data', \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y')]);
            fputcsv($out, ['Cliente', $nomeCliente]);
            fputcsv($out, ['Revendedora', $nomeRevendedora]);
            fputcsv($out, ['Status', $pedido->status]);
            fputcsv($out, ['Valor Total', number_format((float)$pedido->valor_total, 2, ',', '.')]);
            fputcsv($out, ['Valor Desconto', number_format((float)$pedido->valor_desconto, 2, ',', '.')]);
            fputcsv($out, ['Valor Líquido', number_format((float)$pedido->valor_liquido, 2, ',', '.')]);
            fputcsv($out, []); // linha em branco

            // Cabeçalho dos itens
            fputcsv($out, ['#', 'CODFAB', 'Produto', 'Qtd', 'R$ Unit', 'R$ Total']);

            foreach ($pedido->itens as $i => $item) {
                fputcsv($out, [
                    $i + 1,
                    $item->codfabnumero ?? ($item->produto->codfabnumero ?? ''),
                    $item->produto->nome ?? '',
                    (string)$item->quantidade,
                    number_format((float)$item->preco_unitario, 2, ',', '.'),
                    number_format((float)$item->preco_total, 2, ',', '.'),
                ]);
            }

            fclose($out);
        }, $arquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Remove o pedido e seus itens.
     */
    public function destroy($id)
    {
        \DB::beginTransaction();
        try {
            $pedido = PedidoVenda::findOrFail($id);

            // Apaga itens primeiro (se não houver ON DELETE CASCADE no banco)
            ItemVenda::where('pedido_id', $pedido->id)->delete();

            // Apaga o pedido
            $pedido->delete();

            \DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido excluído com sucesso.');
        } catch (\Throwable $e) {
            \DB::rollBack();
            return redirect()->route('vendas.index')->with('error', 'Erro ao excluir: '.$e->getMessage());
        }
    }

    /**
     * (Placeholder) Editar — implementamos na próxima etapa.
     * Por enquanto, apenas informa que será disponibilizado.
     */

public function edit($id)
{
    $pedido = PedidoVenda::with(['itens.produto:id,nome,codfabnumero', 'cliente:id,nome', 'revendedora:id,nome'])
        ->findOrFail($id);

    $clientes     = \App\Models\Cliente::orderBy('nome')->get(['id','nome']);
    $revendedoras = \App\Models\Revendedora::orderBy('nome')->get(['id','nome']);
    $formas       = \App\Models\FormaPagamento::orderBy('nome')->get(['id','nome']);

    // planos da forma atual do pedido
    $planos = \App\Models\PlanoPagamento::where('formapagamento_id', $pedido->formapagamento_id)
                ->orderBy('descricao')->get(['id','descricao']);

    // produtos para o select
    $produtos = \App\Models\Produto::orderBy('nome')->get(['id','nome','codfabnumero']);

    return view('vendas.edit', compact('pedido','clientes','revendedoras','formas','planos','produtos'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'cliente_id'         => 'required|integer|exists:appcliente,id',
        'revendedora_id'     => 'nullable|integer|exists:apprevendedora,id',
        'forma_pagamento_id' => 'required|integer|exists:appformapagamento,id',
        'plano_pagamento_id' => 'required|integer|exists:appplanopagamento,id',
        'data_pedido'        => 'nullable|date',
        'previsao_entrega'   => 'nullable|date',
        'observacao'         => 'nullable|string',
        'valor_desconto'     => 'nullable|numeric|min:0',

        'itens'                  => 'required|array|min:1',
        'itens.*.produto_id'     => 'required|integer|exists:appproduto,id',
        'itens.*.codfabnumero'   => 'nullable|string|max:30',
        'itens.*.quantidade'     => 'required|integer|min:1',
        'itens.*.preco_unitario' => 'required|numeric|min:0',
    ]);

    \DB::beginTransaction();
    try {
        $pedido = PedidoVenda::findOrFail($id);

        // recalcula totais
        $total = 0;
        foreach ($request->itens as $it) {
            $q  = (int)$it['quantidade'];
            $pu = (float)$it['preco_unitario'];
            $total += $q * $pu;
        }
        $desconto = (float)($request->valor_desconto ?? 0);
        $liquido  = max(0, $total - $desconto);

        // atualiza cabeçalho
        $pedido->update([
            'cliente_id'         => $request->cliente_id,
            'revendedora_id'     => $request->revendedora_id,
            'data_pedido'        => $request->data_pedido ?: $pedido->data_pedido,
            'previsao_entrega'   => $request->previsao_entrega,
            'status'             => $pedido->status ?? 'PENDENTE',
            'forma_pagamento_id' => $request->forma_pagamento_id,
            'valor_total'        => $total,
            'valor_desconto'     => $desconto,
            'valor_liquido'      => $liquido,
            'observacao'         => $request->observacao,
        ]);

        // recria itens (simples e seguro)
        \App\Models\ItemVenda::where('pedido_id', $pedido->id)->delete();
        foreach ($request->itens as $it) {
            \App\Models\ItemVenda::create([
                'pedido_id'      => $pedido->id,
                'produto_id'     => $it['produto_id'],
                'codfabnumero'   => $it['codfabnumero'] ?? null,
                'quantidade'     => (int)$it['quantidade'],
                'preco_unitario' => (float)$it['preco_unitario'],
                'preco_total'    => (int)$it['quantidade'] * (float)$it['preco_unitario'],
                'reservado'      => 0,
                'entregue'       => 0,
            ]);
        }

        \DB::commit();
        return redirect()->route('vendas.index')->with('success', 'Pedido atualizado com sucesso!');
    } catch (\Throwable $e) {
        \DB::rollBack();
        return back()->with('error', 'Erro ao atualizar: '.$e->getMessage())->withInput();
    }
}


}
