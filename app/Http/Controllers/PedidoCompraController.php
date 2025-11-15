<?php

namespace App\Http\Controllers;

use App\Models\PedidoCompra;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\ItensCompra;
use App\Models\ViewProduto;
use App\Models\FormaPagamento;
use App\Models\PlanoPagamento;
use App\Models\ContasPagar;
use App\Models\BaixaPagar;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EstoqueService;
use App\Services\Financeiro\GerarContasPagarDaCompra;


use Illuminate\Support\Facades\Log;


class PedidoCompraController extends Controller
{
    /**
     * Exibe a lista de pedidos de compra
     */
    public function index(Request $request)
    {
        $query = PedidoCompra::with('fornecedor')->orderByDesc('id');

        // Filtros
        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        if ($request->filled('status') && in_array($request->status, ['PENDENTE', 'RECEBIDA', 'CANCELADA'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('data_ini')) {
            $query->whereDate('data_compra', '>=', $request->data_ini);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_compra', '<=', $request->data_fim);
        }

        // Busca os pedidos filtrados
        $pedidos = $query->get();

        // Resumo dos valores
        $totalCompra   = $pedidos->sum('valor_total');
        $totalLiquido  = $pedidos->sum('valor_liquido');
        $totalVenda    = $pedidos->sum('preco_venda_total');
        $lucro         = $pedidos->sum(function ($p) {
            $vl = $p->valor_liquido      ?? 0;
            $tv = $p->preco_venda_total  ?? 0;
            return $tv - $vl;
        });

        $resumo = [
            'total_compra'  => $totalCompra,
            'total_liquido' => $totalLiquido,
            'total_venda'   => $totalVenda,
            'lucro'         => $lucro,
        ];

        // Fornecedores para o filtro
        $fornecedores = Fornecedor::orderBy('nomefantasia')->get();

        return view('compras.index', [
            'pedidos'      => $pedidos,
            'fornecedores' => $fornecedores,
            'resumo'       => $resumo,
            'filtros'      => $request->only(['fornecedor_id', 'status', 'data_ini', 'data_fim']),
        ]);
    }

    /**
     * Mostra o formulário de nova compra
     */
    public function create()
    {
        $fornecedores      = Fornecedor::orderBy('razaosocial')->get();
        $produtos          = Produto::orderBy('nome')->get();
        $formasPagamento   = FormaPagamento::orderBy('nome')->get();
        $planosPagamento   = PlanoPagamento::orderBy('descricao')->get();

        return view('compras.create', compact(
            'fornecedores',
            'produtos',
            'formasPagamento',
            'planosPagamento'
        ));
    }


    /**
     * Grava o pedido de compra e seus itens
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'fornecedor_id'        => 'required|integer',
            'data_pedido'          => 'required|date',
            'observacao'           => 'nullable|string|max:1000',

            'forma_pagamento_id'   => 'nullable|integer',
            'plano_pagamento_id'   => 'nullable|integer',
            'qt_parcelas'          => 'nullable|integer|min:1',

            'itens'                => 'required|array|min:1',
            'itens.*.produto_id'   => 'required|integer',
            'itens.*.codfabnumero' => 'nullable|string',
            'itens.*.quantidade'   => 'required|numeric|min:1',
            'itens.*.desconto'     => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            $totalBrutoCusto   = 0.0; // soma dos custos brutos
            $totalDesconto     = 0.0; // soma dos descontos dos itens
            $totalLiquido      = 0.0; // soma dos custos líquidos
            $totalRevenda      = 0.0; // soma dos valores de revenda
            $totalPontosGeral  = 0.0; // soma de pontos
            $qtditens          = 0;   // quantidade de linhas

            $itensCalc = [];

            foreach ($data['itens'] as $idx => $item) {

                $codfab    = $item['codfabnumero'] ?? null;
                $produtoId = $item['produto_id']   ?? null;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (! $vp) {
                    abort(422, "Produto inválido na linha " . ($idx + 1) . " (código não encontrado na view).");
                }

                $qtd          = (float) $item['quantidade'];
                $precoCompra  = (float) $vp->preco_compra;
                $precoRevenda = (float) $vp->preco_revenda;
                $pontosUnit   = (float) $vp->pontos;
                $descLinha    = isset($item['desconto']) ? (float) $item['desconto'] : 0.0;
                if ($descLinha < 0) {
                    $descLinha = 0;
                }

                $totalItemCustoBruto = $qtd * $precoCompra;
                $totalItemLiquido    = max(0, $totalItemCustoBruto - $descLinha);
                $totalItemRevenda    = $qtd * $precoRevenda;
                $pontosLinha         = $qtd * $pontosUnit;

                $totalBrutoCusto   += $totalItemCustoBruto;
                $totalDesconto     += $descLinha;
                $totalLiquido      += $totalItemLiquido;
                $totalRevenda      += $totalItemRevenda;
                $totalPontosGeral  += $pontosLinha;
                $qtditens++;

                $itensCalc[] = [
                    'produto_id'      => $produtoId,
                    'quantidade'      => $qtd,
                    'preco_compra'    => $precoCompra,
                    'preco_revenda'   => $precoRevenda,
                    'total_custo'     => $totalItemCustoBruto,
                    'total_liquido'   => $totalItemLiquido,
                    'total_revenda'   => $totalItemRevenda,
                    'pontos_unit'     => $pontosUnit,
                    'pontos_total'    => $pontosLinha,
                    'valor_desconto'  => $descLinha,
                ];
            }

            // ==== CABEÇALHO: tabela appcompra ====
$compraId = DB::table('appcompra')->insertGetId([
    'fornecedor_id'      => $data['fornecedor_id'],
    'data_compra'        => $data['data_pedido'],
    'data_emissao'       => $data['data_pedido'],
    'numpedcompra'       => null,
    'numero_nota'        => null,

    'valor_total'        => $totalBrutoCusto,
    'valor_desconto'     => $totalDesconto,
    'valor_liquido'      => $totalLiquido,

    'preco_venda_total'  => $totalRevenda,
    'pontostotal'        => $totalPontosGeral,
    'qtditens'           => $qtditens,

    // guarda IDs
    'forma_pagamento_id' => $data['forma_pagamento_id'] ?? null,
    'plano_pagamento_id' => $data['plano_pagamento_id'] ?? null,
    'qt_parcelas'        => $data['qt_parcelas'] ?? null,

    // se quiser, pode depois alimentar 'formapgto' com a descrição
    'formapgto'          => null,

    'observacao'         => $data['observacao'] ?? null,
    'status'             => 'PENDENTE',

    'created_at'         => now(),
    'updated_at'         => now(),
]);


            // ==== ITENS: tabela appcompraproduto ====
            foreach ($itensCalc as $it) {
                DB::table('appcompraproduto')->insert([
                    'compra_id'            => $compraId,
                    'produto_id'           => $it['produto_id'],
                    'quantidade'           => $it['quantidade'],

                    'preco_unitario'       => $it['preco_compra'],
                    'valor_desconto'       => $it['valor_desconto'],
                    'total_item'           => $it['total_custo'],
                    'total_liquido'        => $it['total_liquido'],

                    'preco_venda_unitario' => $it['preco_revenda'],
                    'preco_venda_total'    => $it['total_revenda'],

                    'pontos'               => $it['pontos_unit'],
                    'pontostotal'          => $it['pontos_total'],

                    'qtd_disponivel'       => $it['quantidade'],

                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            return redirect()
                ->route('compras.index')
                ->with('success', 'Compra salva com sucesso (descontos por item e cabeçalho somado).');
        });
    }



    /**
     * Exibe os detalhes da compra
     */
    public function show($id)
    {
        $pedido = PedidoCompra::with(['fornecedor', 'itens.produto'])->findOrFail($id);
        return view('compras.show', compact('pedido'));
    }

    /**
     * Edita o pedido (ex: confirmar recebimento)
     */
    public function edit($id)
    {
        $pedido = PedidoCompra::with(['itens.produto', 'fornecedor'])->findOrFail($id);

        $fornecedores = Fornecedor::orderBy('nomefantasia')->get();
        $produtos = Produto::orderBy('nome')->get();

        return view('compras.edit', [
            'pedido' => $pedido,
            'fornecedores' => $fornecedores,
            'produtos' => $produtos,
            'numeroPedido' => $pedido->numpedcompra ?? '(sem número)'
        ]);
    }


    /**
     * Atualiza a compra
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $pedido = PedidoCompra::with(['itens.produto'])->findOrFail($id);

            // Guarda status antigo pra evitar duplicar processos
            $jaRecebidaAntes = ($pedido->status === 'RECEBIDA');

            // Atualiza cabeçalho
            $pedido->update([
                'numpedcompra'      => $request->numpedcompra,
                'numero_nota'       => $request->numero_nota,   // <= aqui
                'valor_total'       => $request->valor_total,
                'preco_venda_total' => $request->preco_venda_total,
                'status'            => $request->acao === 'confirmar' ? 'RECEBIDA' : 'PENDENTE',
                'formapgto'         => $request->formapgto ?? $pedido->formapgto,
                'qt_parcelas'       => $request->qt_parcelas ?? $pedido->qt_parcelas,
            ]);

            // Atualiza os itens (usando produto_id como referência)
            foreach ($request->itens as $dados) {
                $item = $pedido->itens->where('produto_id', $dados['produto_id'])->first();

                if ($item) {
                    $item->update([
                        'qtd_disponivel'       => ($dados['quantidade'] ?? $item->quantidade),
                        'preco_unitario'       => ($dados['preco_unitario'] ?? $item->preco_unitario),
                        'preco_venda_unitario' => ($dados['preco_venda_unitario'] ?? $item->preco_venda_unitario),
                        'pontos'               => ($dados['pontos'] ?? $item->pontos),
                    ]);
                }
            }

            // Se confirmar recebimento, registra movimentação no estoque
            if ($request->acao === 'confirmar' && ! $jaRecebidaAntes) {
                // 1) Atualiza estoque
                $estoqueService = new EstoqueService();
                $estoqueService->registrarEntradaCompra($pedido);

                // 2) Gera contas a pagar
                $financeiroService = new GerarContasPagarDaCompra();
                $financeiroService->executar($pedido);
            }

            DB::commit();

            $msg = $request->acao === 'confirmar'
                ? 'Recebimento confirmado, estoque atualizado e contas a pagar geradas!'
                : 'Pedido salvo com sucesso!';

            return redirect()->route('compras.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();

            log::error('Erro ao atualizar pedido de compra', [
                'mensagem' => $e->getMessage(),
                'linha'    => $e->getLine(),
                'arquivo'  => $e->getFile(),
            ]);

            return back()->with('error', 'Erro ao atualizar o pedido: ' . $e->getMessage());
        }
    }


    /**
     * Exibe o formulário de importação de itens
     */
    public function importarItens($id)
    {
        $pedido = PedidoCompra::findOrFail($id);
        return view('compras.importar', compact('pedido'));
    }

    /**
     * Processa o upload e importa os itens (CSV simples)
     */
    public function processarImportacao(Request $request, $id)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $pedido = PedidoCompra::findOrFail($id);

        $caminho = $request->file('arquivo')->getRealPath();

        // Lê o arquivo inteiro
        $linhas = array_map('str_getcsv', file($caminho));

        $importados = 0;

        foreach ($linhas as $linha) {
            // Espera formato:
            // CODIGO;QUANTIDADE;PRECO_COMPRA;PONTOS;PRECO_REVENDA
            // Ex.: 123456;10;19,90;5;29,90

            $codigo       = trim($linha[0] ?? '');
            $qtd          = (float) str_replace(',', '.', $linha[1] ?? 0);
            $pontos       = isset($linha[2]) ? (float) str_replace(',', '.', $linha[2]) : 0;
            $precoCompra  = isset($linha[3]) ? (float) str_replace(',', '.', $linha[3]) : 0;
            $precoRevenda = isset($linha[4]) ? (float) str_replace(',', '.', $linha[4]) : 0;

            if ($codigo === '' || $qtd <= 0) {
                continue;
            }

            // pode ajustar aqui: codfab ou codfabnumero
            $produto = \App\Models\Produto::where('codfab', $codigo)
                ->orWhere('codfabnumero', $codigo)
                ->first();

            if (!$produto) {
                continue;
            }

            \App\Models\ItensCompra::create([
                'compra_id'            => $pedido->id,
                'produto_id'           => $produto->id,
                'quantidade'           => $qtd,
                'preco_unitario'       => $precoCompra,
                'qtd_disponivel'       => $qtd,
                'pontos'               => $pontos,
                'preco_venda_unitario' => $precoRevenda,
            ]);

            $importados++;
        }

        return redirect()->route('compras.edit', $pedido->id)
            ->with('success', "Importação concluída: {$importados} itens adicionados.");
    }

    /**
     * Exporta os itens do pedido de compra em formato CSV
     */
    public function exportarItens($id)
    {
        $pedido = PedidoCompra::with(['itens.produto'])->findOrFail($id);

        $filename = 'pedido_compra_' . $pedido->id . '.csv';

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Codigo', 'Quantidade'], ';');

        foreach ($pedido->itens as $item) {
            fputcsv($handle, [
                $item->produto->codfab ?? '',
                intval($item->quantidade),
            ], ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Remove um pedido
     */

public function destroy(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $pedido = PedidoCompra::with([
                'itens.produto',
                'contasPagar.baixas',
            ])->findOrFail($id);

        $motivo = $request->motivo_cancelamento ?? 'Sem motivo informado';

        /**
         * REGRA 2 – Se tiver qualquer parcela com baixa (paga), não pode cancelar
         */
        $parcelasComBaixa = $pedido->contasPagar
            ->filter(fn ($c) => $c->baixas->isNotEmpty());

        if ($parcelasComBaixa->isNotEmpty()) {
            $listaParcelas = $parcelasComBaixa->map(function ($c) {
                return sprintf(
                    'Parcela %d/%d - Nota %s - Venc.: %s - Valor: R$ %s',
                    $c->parcela,
                    $c->total_parcelas,
                    $c->numero_nota ?? '-',
                    optional($c->data_vencimento)->format('d/m/Y'),
                    number_format($c->valor, 2, ',', '.')
                );
            })->toArray();

            DB::rollBack();

            return back()
                ->with('error', 'Cancelamento não permitido: existem parcelas desta compra que já possuem baixa (pagas).')
                ->with('parcelas_pagas', $listaParcelas);
        }

        /**
         * REGRA 1 – Verificar se o estorno do estoque deixaria algum item negativo
         * (só faz sentido se a compra foi RECEBIDA)
         */
        $itensComEstoqueNegativo = [];

        if ($pedido->status === 'RECEBIDA') {
            foreach ($pedido->itens as $item) {
                $produto = $item->produto;

                // usa a view de produtos pra pegar estoque atual
                $viewProd = null;
                if ($produto && $produto->codfabnumero) {
                    $viewProd = \App\Models\ViewProduto::where('codigo_fabrica', $produto->codfabnumero)->first();
                }

                $estoqueAtual = $viewProd->qtd_estoque ?? 0; // ajuste pro nome da coluna da sua view
                $qtdEstornar  = (float) $item->quantidade;

                $saldoPosEstorno = $estoqueAtual - $qtdEstornar;

                if ($saldoPosEstorno < 0) {
                    $itensComEstoqueNegativo[] = [
                        'produto'          => $produto->nome ?? ('ID ' . $produto->id),
                        'codigo_fabrica'   => $produto->codfabnumero ?? null,
                        'estoque_atual'    => $estoqueAtual,
                        'qtd_estornar'     => $qtdEstornar,
                        'saldo_pos_estorno'=> $saldoPosEstorno,
                    ];
                }
            }
        }

        if (!empty($itensComEstoqueNegativo)) {
            DB::rollBack();

            return back()
                ->with('error', 'Cancelamento não permitido: o estorno deixaria o estoque negativo para um ou mais itens.')
                ->with('itens_estoque_negativo', $itensComEstoqueNegativo);
        }

        /**
         * REGRA 3 – Se passou pelas regras 1 e 2, o cancelamento é permitido
         * - Estorna estoque (se RECEBIDA)
         * - Estorna/cancela parcelas de Contas a Pagar
         * - NÃO exclui o pedido nem os itens — apenas marca como CANCELADA
         */

        // 3.1 Estorna estoque (se foi recebida)
        if ($pedido->status === 'RECEBIDA') {
            $service = new \App\Services\EstoqueService();
            $service->estornarEntradaCompra($pedido, $motivo);
        }

        // 3.2 Cancelar/estornar parcelas de Contas a Pagar
        foreach ($pedido->contasPagar as $conta) {

            // Por segurança, remove qualquer baixa pendente (em teoria não deveria ter, por causa da Regra 2)
            foreach ($conta->baixas as $baixa) {
                $baixa->delete();
            }

            $textoObs = trim(
                ($conta->observacao ? $conta->observacao . "\n" : '') .
                'Parcela cancelada ao cancelar o pedido de compra #' . $pedido->id .
                ' | Motivo: ' . $motivo
            );

            $conta->status         = 'CANCELADO';
            $conta->valor_pago     = null;
            $conta->data_pagamento = null;
            $conta->observacao     = $textoObs;
            $conta->save();
        }

        // 3.3 Atualiza o pedido para CANCELADA (sem excluir)
        $textoObsPedido = trim(
            ($pedido->observacao ? $pedido->observacao . "\n" : '') .
            'Pedido cancelado. Motivo: ' . $motivo
        );

        $pedido->status     = 'CANCELADA';
        $pedido->observacao = $textoObsPedido;
        $pedido->save(); // 👈 NÃO exclui, só salva com status cancelado

        DB::commit();

        return redirect()
            ->route('compras.index')
            ->with('success', 'Pedido cancelado com sucesso. Estoque e parcelas de Contas a Pagar foram estornados.');
    } catch (\Exception $e) {
        DB::rollBack();

        return back()->with('error', 'Erro ao cancelar pedido: ' . $e->getMessage());
    }
}

}
