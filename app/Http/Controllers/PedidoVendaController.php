<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Symfony\Component\HttpFoundation\StreamedResponse;

use Carbon\Carbon;

use App\Models\PedidoVenda;
use App\Models\ItemVenda;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\FormaPagamento;
use App\Models\PlanoPagamento;
use App\Models\Produto;
use App\Models\ViewProduto;

use App\Services\EstoqueService;
use App\Services\ContasReceberService;
use App\Services\CampaignEvaluatorService; // <<<

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
     * Lista pedidos (com filtros: cliente, período e status)
     */
    public function index(Request $request)
    {
        $q = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome']);

        // Filtro por status
        $status = strtoupper((string)$request->query('status', ''));
        if ($status === 'PENDENTES') {
            $q->whereIn('status', ['PENDENTE', 'ABERTO']);
        } elseif (in_array($status, ['PENDENTE', 'ABERTO', 'ENTREGUE', 'CANCELADO'])) {
            $q->where('status', $status);
        }

        // (espaço pra filtros extras no futuro, ex: cliente, datas etc.)
        $pedidos = $q->orderByDesc('id')->paginate(10)->appends($request->query());

        return view('vendas.index', compact('pedidos'));
    }
    private function normalizeMovEnums(string $tipo, ?string $status): array
    {
        // normaliza tipo_mov
        $tipo = strtoupper(trim($tipo));
        $mapTipo = [
            'RESERVA_SAIDA'   => 'RESERVA_SAIDA',
            'RESERVA- SAIDA'  => 'RESERVA_SAIDA',
            'SAIDA_RESERVA'   => 'RESERVA_SAIDA',
            'RESERVA_ENTRADA' => 'RESERVA_ENTRADA',
            'RESERVA- ENTRADA' => 'RESERVA_ENTRADA',
            'ENTRADA_RESERVA' => 'RESERVA_ENTRADA',
            'ENTRADA'         => 'ENTRADA',
            'SAIDA'           => 'SAIDA',
            'AJUSTE'          => 'AJUSTE',
            // sinônimos antigos:
            'RESERVA'         => 'RESERVA_SAIDA',
            'DEVOLUCAO'       => 'ENTRADA',
            'BAIXA'           => 'SAIDA',
        ];
        $tipo = $mapTipo[$tipo] ?? $tipo;

        // normaliza status
        $status = $status !== null ? strtoupper(trim($status)) : null;
        $mapStatus = [
            'RESERVADO'     => 'RESERVADO',
            'CONFIRMADO'    => 'CONFIRMADO',
            'CANCELADO'     => 'CANCELADO',
            // sinônimos antigos que causavam truncation:
            'RESERVA'       => 'RESERVADO',
            'CANCELAMENTO'  => 'CANCELADO',
            'CONFIRMADA'    => 'CONFIRMADO',
        ];
        if ($status !== null) {
            $status = $mapStatus[$status] ?? $status;
        }

        return [$tipo, $status];
    }

    /**
     * Formulário de novo pedido
     */
    public function create()
    {
        $clientes     = Cliente::orderBy('nome')->get(['id', 'nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id', 'nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id', 'nome']);
        $produtos     = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        $revendedoraPadraoId = Revendedora::where('revenda_padrao', 1)->value('id'); // pode retornar null

        return view('vendas.create', compact(
            'clientes',
            'revendedoras',
            'formas',
            'produtos',
            'revendedoraPadraoId'
        ));
    }

    /**
     * Salva um novo pedido (status PENDENTE) e RESERVA estoque
     * + aplica campanhas no salvar (idempotente por reavaliação)
     */

    public function store(Request $request)
    {
        // ===== ajuste rápido de nomes, se seu legado usar outros =====
        $TAB_PEDIDO  = 'apppedidovenda'; // cabeçalho do pedido
        $TAB_ITENS   = 'appitemvenda';    // itens do pedido (troque p/ apppedidovendaitem se for o caso)
        $TAB_ESTOQUE = 'appestoque';     // estoque atual
        $TAB_MOV     = 'appmovestoque';  // movimentações de estoque

        // colunas de estoque
        $COL_DISP    = 'disponivel';
        $COL_RESERVA = 'reservado';      // se for 'reserva' no seu banco, troque aqui

        // ===== 1) Validação =====
        $data = $request->validate([
            'cliente_id'          => 'required|integer',
            'revendedora_id'      => 'nullable|integer',
            'forma_pagamento_id'  => 'required|integer',
            'plano_pagamento_id'  => 'required|integer',
            'data_pedido'         => 'required|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string|max:1000',
            'desconto'            => 'nullable|numeric|min:0',
            'itens'               => 'required|array|min:1',
            'itens.*.produto_id'  => 'required|integer',
            'itens.*.codfabnumero' => 'nullable|string',
            'itens.*.quantidade'  => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data, $TAB_PEDIDO, $TAB_ITENS, $TAB_ESTOQUE, $TAB_MOV, $COL_DISP, $COL_RESERVA) {

            $totalBruto = 0.0;
            $totalPontosUnit = 0;
            $totalPontosGeral = 0;
            $itensCalc = [];

            // ===== 2) Recalcular itens pela ViewProduto =====
            foreach ($data['itens'] as $idx => $item) {
                $codfab = $item['codfabnumero'] ?? null;

                // Se não vier, aqui você pode resolver pelo produto_id na sua tabela de produtos
                // $codfab = $codfab ?: optional(Produto::find($item['produto_id']))->codfabnumero;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (!$vp) {
                    abort(422, "Produto inválido na linha " . ($idx + 1) . " (código/ficha não encontrado).");
                }

                $qtd          = (int) $item['quantidade'];
                $precoUnit    = (float) $vp->preco_revenda;   // VENDA
                $pontosUnit   = (int) $vp->pontos;
                $estoqueAtual = (int) $vp->qtd_estoque;

                // bloqueio: não vende acima do disponível (estoque livre = disponível)
                if ($qtd > $estoqueAtual) {
                    abort(422, "Estoque insuficiente para {$vp->descricao_produto} (disp: {$estoqueAtual}, solicitado: {$qtd}).");
                }

                $totalLinha  = $qtd * $precoUnit;
                $pontosLinha = $qtd * $pontosUnit;


                $totalBruto       += $totalLinha;
                $totalPontosUnit  += $pontosUnit;
                $totalPontosGeral += $pontosLinha;

                $itensCalc[] = [
                    'produto_id'       => $item['produto_id'],
                    'codfabnumero'     => $codfab ?? $vp->codigo_fabrica,
                    'quantidade'       => $qtd,
                    'preco_unitario'   => $precoUnit,
                    'preco_total'      => $totalLinha,
                    'pontuacao'        => $pontosUnit,
                    'pontuacao_total'  => $pontosLinha,
                ];
            }

            $desconto     = (float) ($data['desconto'] ?? 0);
            $totalLiquido = max(0, $totalBruto - $desconto);

            // ===== 3) Grava cabeçalho do pedido (status inicial PENDENTE) =====
            $vendaId = DB::table($TAB_PEDIDO)->insertGetId([
                'cliente_id'         => $data['cliente_id'],
                'revendedora_id'     => $data['revendedora_id'] ?? null,
                'forma_pagamento_id' => $data['forma_pagamento_id'],
                'plano_pagamento_id' => $data['plano_pagamento_id'],
                'data_pedido'        => $data['data_pedido'],
                'previsao_entrega'   => $data['previsao_entrega'] ?? null,
                'observacao'         => $data['observacao'] ?? null,
                'valor_total'        => $totalBruto,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $totalLiquido,
                'pontuacao'          => $totalPontosUnit,
                'pontuacao_total'    => $totalPontosGeral,
                'status'             => 'PENDENTE',
            ]);

            // ===== 4) Grava itens =====
            foreach ($itensCalc as $it) {
                // calcula com fallback caso 'total' não exista no array
                $precoTotal = ($it['total'] ?? ($it['preco_unitario'] * $it['quantidade']));

                DB::table($TAB_ITENS)->insert([
                    // ATENÇÃO: use o nome da FK que existe na sua tabela
                    'pedido_id'        => $vendaId,
                    'produto_id'       => $it['produto_id'],
                    'codfabnumero'     => $it['codfabnumero'],
                    'quantidade'       => $it['quantidade'],
                    'preco_unitario'   => $it['preco_unitario'],
                    'preco_total'      => $precoTotal,
                    'pontuacao'        => $it['pontuacao'],
                    'pontuacao_total'  => $it['pontuacao_total'],
                ]);
            }

            // ===== 5) Reserva de estoque + movimentação (status inicial = PENDENTE) =====
            foreach ($itensCalc as $it) {
                // trava o registro de estoque do produto
                $estq = DB::table($TAB_ESTOQUE)
                    ->lockForUpdate()
                    ->where('codfabnumero', $it['codfabnumero'])
                    ->first();

                if (!$estq) {
                    // cria o registro, se não existir (opcional)
                    DB::table($TAB_ESTOQUE)->insert([
                        'produto_id'   => $it['produto_id'],
                        'codfabnumero' => $it['codfabnumero'],
                        $COL_DISP      => 0,
                        $COL_RESERVA   => 0,
                    ]);
                    $estq = (object)[$COL_DISP => 0, $COL_RESERVA => 0];
                }

                // aumenta a reserva
                $novaReserva = ((int)($estq->{$COL_RESERVA} ?? 0)) + (int)$it['quantidade'];
                DB::table($TAB_ESTOQUE)
                    ->where('codfabnumero', $it['codfabnumero'])
                    ->update([$COL_RESERVA => $novaReserva]);

                // registra movimentação: SAÍDA / RESERVA AO CRIAR
                [$tipoOk, $statusOk] = $this->normalizeMovEnums('RESERVA_SAIDA', 'RESERVADO');

                $mov = [
                    'produto_id'   => $it['produto_id'],
                    'codfabnumero' => $it['codfabnumero'],
                    'tipo_mov'     => 'RESERVA_SAIDA',
                    'status'       => 'RESERVADO',
                    'origem'       => 'VENDA',
                    'quantidade'   => (int)$it['quantidade'],
                    'data_mov'     => now(),
                    'observacao'   => 'Reserva de estoque na criação do pedido',
                ];
                $this->safeInsertMov($TAB_MOV, $mov, $vendaId);
            }

            // (CR/financeiro pode ser gerado aqui, se for seu fluxo)
            return redirect()
                ->route('vendas.index')
                ->with('success', 'Venda salva com sucesso (totais recalculados e estoque reservado).');
        });
    }


    /**
     * Editar pedido
     */
    public function edit($id)
    {
        $pedido = PedidoVenda::with(['itens.produto:id,nome,codfabnumero', 'cliente:id,nome', 'revendedora:id,nome'])
            ->findOrFail($id);

        $clientes     = Cliente::orderBy('nome')->get(['id', 'nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id', 'nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id', 'nome']);
        $planos       = PlanoPagamento::where('formapagamento_id', $pedido->forma_pagamento_id)
            ->orderBy('descricao')
            ->get(['id', 'descricao', 'formapagamento_id', 'parcelas', 'prazo1', 'prazo2', 'prazo3']);
        $produtos     = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        return view('vendas.edit', compact('pedido', 'clientes', 'revendedoras', 'formas', 'planos', 'produtos'));
    }

    /**
     * Atualiza (e reavalia campanhas)
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

            // Reserva se ainda pendente
            if (strtoupper($pedido->status) === 'PENDENTE') {
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

                $pedido->load('itens.produto');
                $this->estoque->reservarVenda($pedido);
            }

            // Reavalia campanhas
            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido atualizado, reservas/parcelas ajustadas e campanhas reavaliadas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao atualizar: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Confirma pedido
     */
    public function confirmar($id)
    {
        $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

        if (!in_array(strtoupper($pedido->status), ['PENDENTE', 'ABERTO'])) {
            return back()->with('info', 'Este pedido não está pendente para confirmação.');
        }

        DB::beginTransaction();
        try {
            $pedido->status = 'ENTREGUE';
            $pedido->save();

            $this->estoque->confirmarSaidaVenda($pedido);

            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido confirmado, reserva liberada, estoque baixado e campanhas reavaliadas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Falha ao confirmar pedido: ' . $e->getMessage());
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
    public function cancelar(Request $request, $id)
    {
        $data = $request->validate([
            'observacao' => 'required|string|min:5|max:2000',
        ]);

        // === CONFIGURE os nomes conforme seu banco ===
        $TAB_PEDIDO   = 'apppedidovenda';
        $TAB_ITENS    = 'appitemvenda';
        $TAB_ESTOQUE  = 'appestoque';
        $TAB_MOV      = 'appmovestoque';
        $TAB_CR       = 'appcontasreceber';

        // Nome da coluna de RESERVA no estoque:
        $COL_RESERVA  = 'reservado';
        $COL_DISP     = 'disponivel';
        DB::transaction(function () use ($id, $data, $TAB_PEDIDO, $TAB_ITENS, $TAB_ESTOQUE, $TAB_MOV, $TAB_CR, $COL_RESERVA, $COL_DISP) {

            // 1) Lock do pedido
            $pedido = DB::table($TAB_PEDIDO)->lockForUpdate()->where('id', $id)->first();
            if (!$pedido) abort(404, 'Pedido não encontrado.');
            if (mb_strtoupper($pedido->status) === 'CANCELADO') {
                abort(422, 'Este pedido já está cancelado.');
            }

            // 2) Itens do pedido
            $itens = DB::table($TAB_ITENS)->where('pedido_id', $id)->get();

            // 3) Para cada item, ajustar estoque conforme STATUS atual
            foreach ($itens as $it) {
                // Lock do registro de estoque deste produto
                $estq = DB::table($TAB_ESTOQUE)
                    ->lockForUpdate()
                    ->where('codfabnumero', $it->codfabnumero)
                    ->first();

                // Se não houver registro de estoque, ignore este item
                if (!$estq) continue;

                $qtd = (int) $it->quantidade;

                if (in_array(mb_strtoupper($pedido->status), ['PENDENTE', 'ABERTO', 'RESERVADO'])) {
                    // 3A) Pedido ainda não entregue: apenas remove a RESERVA
                    // calcula nova reserva (não deixa negativo)
                    $valorReservaAtual = (int) ($estq->{$COL_RESERVA} ?? 0);
                    $novaReserva = max(0, $valorReservaAtual - $qtd);

                    DB::table($TAB_ESTOQUE)
                        ->where('codfabnumero', $it->codfabnumero)
                        ->update([$COL_RESERVA => $novaReserva]);

                    // movimentação: ENTRADA por CANCELAMENTO (origem VENDA) -Cancelar PENDENTE/ABERTO/RESERVADO → liberar reserva
                    [$tipoOk, $statusOk] = $this->normalizeMovEnums('RESERVA_ENTRADA', 'CANCELADO');

                    $mov = [
                        'produto_id'   => $it->produto_id,
                        'codfabnumero' => $it->codfabnumero,
                        'tipo_mov'     => 'RESERVA_ENTRADA',
                        'status'       => 'CANCELADO',
                        'origem'       => 'VENDA',
                        'quantidade'   => (int)$qtd,
                        'data_mov'     => now(),
                        'observacao'   => $data['observacao'] . ' (cancelamento de reserva)',
                    ];
                    $this->safeInsertMov($TAB_MOV, $mov, $id);
                } elseif (mb_strtoupper($pedido->status) === 'ENTREGUE') {
                    // === Pedido ENTREGUE: devolver ao estoque disponível ===
                    // escolha a melhor coluna base gravável (prioridade: estoque_gerencial → fisico → ... )
                    $colBase = $this->firstWritableColumn($TAB_ESTOQUE, [
                        'estoque_gerencial',
                        'fisico',
                        'qtd_fisica',
                        'qtd_total',
                        'quantidade',
                        'qtd',
                        'estoque'
                    ]);

                    if ($colBase) {
                        DB::table($TAB_ESTOQUE)
                            ->where('codfabnumero', $it->codfabnumero)
                            ->increment($colBase, $qtd);
                    } else {
                        // Não achou coluna gravável (ex.: tudo gerado) -> apenas registra movimento
                        // (opcional: log/alert para revisar schema)
                    }

                    // movimento: ENTRADA / CANCELADO (origem VENDA)
                    $mov = [
                        'produto_id'   => $it->produto_id,
                        'codfabnumero' => $it->codfabnumero,
                        'tipo_mov'     => 'ENTRADA',
                        'status'       => 'CANCELADO',
                        'origem'       => 'VENDA',
                        'quantidade'   => (int)$qtd,
                        'data_mov'     => now(),
                        'observacao'   => $data['observacao'],
                    ];
                    $this->safeInsertMov($TAB_MOV, $mov, $id);
                }
            }

            // 4) Pedido -> CANCELADO (sem updated_at)
            DB::table($TAB_PEDIDO)->where('id', $id)->update([
                'status'           => 'CANCELADO',
                'obs_cancelamento' => $data['observacao'],
                'canceled_at'      => now(),
            ]);

            // 5) Títulos (CR) -> CANCELADO (mesma observação)
            DB::table($TAB_CR)->where('pedido_id', $id)->update([
                'status'           => 'CANCELADO',
                'obs_cancelamento' => $data['observacao'],
                'canceled_at'      => now(),
            ]);
        });

        return back()->with('success', 'Pedido cancelado, estoque/títulos ajustados.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

            if (strtoupper($pedido->status) === 'PENDENTE') {
                $this->estoque->cancelarReservaVenda($pedido);
            }

            $this->cr->cancelarAbertasPorPedido($pedido->id);

            ItemVenda::where('pedido_id', $pedido->id)->delete();
            $pedido->delete();

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido excluído com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('vendas.index')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }
    private function enumValues(string $table, string $column): array
    {
        $row = DB::selectOne("
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ", [$table, $column]);

        if (!$row || !isset($row->COLUMN_TYPE)) return [];
        if (preg_match("/^enum\\((.*)\\)$/i", $row->COLUMN_TYPE, $m)) {
            $vals = array_map(fn($v) => trim($v, " '"), explode(',', $m[1]));
            return array_map('strtoupper', $vals);
        }
        return [];
    }

    private function safeInsertMov(string $table, array $mov, ?int $pedidoId = null): void
    {
        // normaliza/enforce enums
        if (isset($mov['tipo_mov'])) {
            $allowedTipo = $this->enumValues($table, 'tipo_mov');
            $mov['tipo_mov'] = strtoupper($mov['tipo_mov']);
            if (!in_array($mov['tipo_mov'], $allowedTipo, true)) unset($mov['tipo_mov']);
        }
        if (isset($mov['status'])) {
            $allowedStatus = $this->enumValues($table, 'status');
            $mov['status'] = strtoupper($mov['status']);
            if (!in_array($mov['status'], $allowedStatus, true)) unset($mov['status']);
        }

        // origem_id = pedido_id (quando existir)
        if ($pedidoId !== null) {
            if (Schema::hasColumn($table, 'origem_id'))  $mov['origem_id'] = $pedidoId;
            if (Schema::hasColumn($table, 'pedido_id'))  $mov['pedido_id'] = $pedidoId; // fallback se usa esse nome
        }

        // timestamps, se existirem
        $now = now();
        if (Schema::hasColumn($table, 'created_at') && empty($mov['created_at']))  $mov['created_at'] = $now;
        if (Schema::hasColumn($table, 'updated_at') && empty($mov['updated_at']))  $mov['updated_at'] = $now;

        DB::table($table)->insert($mov);
    }
    private function isGeneratedColumn(string $table, string $column): bool
    {
        $row = DB::selectOne("
        SELECT EXTRA
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ", [$table, $column]);

        if (!$row || !isset($row->EXTRA)) return false;
        $extra = strtoupper($row->EXTRA);
        return str_contains($extra, 'GENERATED'); // VIRTUAL/ STORED GENERATED
    }

    private function firstWritableColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c) && !$this->isGeneratedColumn($table, $c)) {
                return $c;
            }
        }
        return null;
    }
    public function confirmarEntrega(int $id)
    {
        // nomes das tabelas/colunas (ajuste se necessário)
        $TAB_PEDIDO  = 'apppedidovenda';
        $TAB_ITENS   = 'appitemvenda';      // se for apppedidovendaitem, troque
        $TAB_ESTOQUE = 'appestoque';
        $TAB_MOV     = 'appmovestoque';
        $COL_RESERVA = 'reservado';        // se sua coluna for 'reserva', troque aqui

        DB::transaction(function () use ($id, $TAB_PEDIDO, $TAB_ITENS, $TAB_ESTOQUE, $TAB_MOV, $COL_RESERVA) {
            // 1) Lock do pedido
            $pedido = DB::table($TAB_PEDIDO)->lockForUpdate()->where('id', $id)->first();
            if (!$pedido) abort(404, 'Pedido não encontrado.');

            $statusAtual = strtoupper($pedido->status ?? '');
            if (in_array($statusAtual, ['CANCELADO', 'ENTREGUE'])) {
                abort(422, 'Este pedido já está finalizado (' . $statusAtual . ').');
            }

            // 2) Itens
            $itens = DB::table($TAB_ITENS)->where('pedido_id', $id)->get();

            // 3) Para cada item: baixa reserva e baixa físico (coluna base gravável)
            foreach ($itens as $it) {
                $qtd = (int) $it->quantidade;

                // Lock do estoque do produto
                $estq = DB::table($TAB_ESTOQUE)
                    ->lockForUpdate()
                    ->where('codfabnumero', $it->codfabnumero)
                    ->first();

                if (!$estq) continue;

                // 3.1) Abate RESERVA
                $reservaAtual = (int)($estq->{$COL_RESERVA} ?? 0);
                $novaReserva  = max(0, $reservaAtual - $qtd);

                DB::table($TAB_ESTOQUE)
                    ->where('codfabnumero', $it->codfabnumero)
                    ->update([$COL_RESERVA => $novaReserva]);

                // 3.2) Baixa do físico (coluna base gravável) — NUNCA mexer em 'disponivel' se for gerada
                $colBase = $this->firstWritableColumn($TAB_ESTOQUE, [
                    'estoque_gerencial',
                    'fisico',
                    'qtd_fisica',
                    'qtd_total',
                    'quantidade',
                    'qtd',
                    'estoque'
                ]);
                if ($colBase) {
                    DB::table($TAB_ESTOQUE)
                        ->where('codfabnumero', $it->codfabnumero)
                        ->decrement($colBase, $qtd);
                }

                // 3.3) Movimentação: SAÍDA / CONFIRMADO (origem VENDA)
                $mov = [
                    'produto_id'   => $it->produto_id,
                    'codfabnumero' => $it->codfabnumero,
                    'tipo_mov'     => 'SAIDA',
                    'status'       => 'CONFIRMADO',
                    'origem'       => 'VENDA',
                    'quantidade'   => $qtd,
                    'data_mov'     => now(),
                    'observacao'   => 'Baixa por entrega confirmada',
                ];
                $this->safeInsertMov($TAB_MOV, $mov, $id);
            }

            // 4) Atualiza status do pedido para ENTREGUE
            DB::table($TAB_PEDIDO)->where('id', $id)->update([
                'status' => 'ENTREGUE',
                // se tiver delivered_at/entregue_em, pode setar aqui:
                // 'delivered_at' => now(),
            ]);

            // ⚠️ Removido: não vamos mudar status das contas aqui,
            // porque elas serão criadas agora (se ainda não existirem).
        });

        // 5) FORA da transação: gerar Contas a Receber (apenas se ainda não existirem)
        $temCr = DB::table('appcontasreceber')->where('pedido_id', $id)->exists();
        if (!$temCr) {
            $pedidoModel = PedidoVenda::find($id);
            if ($pedidoModel) {
                // Gera parcelas em status ABERTO
                $this->cr->gerarParaPedido($pedidoModel);
            }
        }

        return back()->with('success', 'Entrega confirmada com sucesso. Contas a receber geradas.');
    }
}
