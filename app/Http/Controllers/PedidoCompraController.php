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
            'data_entrega'         => 'nullable|date',
            'encargos'             => 'nullable|numeric|min:0',
            'observacao'           => 'nullable|string|max:1000',

            'forma_pagamento_id'   => 'nullable|integer',
            'plano_pagamento_id'   => 'nullable|integer',
            'qt_parcelas'          => 'nullable|integer|min:1',

            'itens'                    => 'required|array|min:1',
            'itens.*.produto_id'       => 'required|integer',
            'itens.*.codfabnumero'     => 'nullable|string',
            'itens.*.quantidade'       => 'required|numeric|min:1',
            'itens.*.desconto'         => 'nullable|numeric|min:0',

            // CAMPOS vindos da tela / importação
            'itens.*.preco_compra'     => 'nullable|numeric|min:0',
            'itens.*.preco_revenda'    => 'nullable|numeric|min:0',
            'itens.*.pontos'           => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            $totalBrutoCusto   = 0.0; // soma dos custos brutos (preço tabela)
            $totalDesconto     = 0.0; // soma dos descontos dos itens (manual + automático)
            $totalLiquido      = 0.0; // soma dos custos líquidos (sem encargos)
            $totalRevenda      = 0.0; // soma dos valores de revenda
            $totalPontosGeral  = 0.0; // soma de pontos
            $qtditens          = 0;   // quantidade de linhas

            $itensCalc         = [];

            // Encargos financeiros informados no cabeçalho
            $encargosCompra    = isset($data['encargos']) ? (float) $data['encargos'] : 0.0;

            foreach ($data['itens'] as $idx => $item) {

                $codfab    = $item['codfabnumero'] ?? null;
                $produtoId = $item['produto_id']   ?? null;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (! $vp) {
                    abort(422, "Produto inválido na linha " . ($idx + 1) . " (código não encontrado na view).");
                }

                $qtd = (float) $item['quantidade'];

                // PREÇOS DA TABELA (VIEW)
                $precoTabelaCompra  = (float) $vp->preco_compra;
                $precoTabelaRevenda = (float) $vp->preco_revenda;
                $pontosTabela       = (float) $vp->pontos;

                // VALORES INFORMADOS PELO FORMULÁRIO / IMPORTAÇÃO
                $precoImportadoCompra  = isset($item['preco_compra'])     ? (float) $item['preco_compra']     : null;
                $precoImportadoRevenda = isset($item['preco_revenda'])    ? (float) $item['preco_revenda']    : null;
                $pontosInformados      = isset($item['pontos'])           ? (float) $item['pontos']           : null;
                $descManualLinha       = isset($item['desconto'])         ? (float) $item['desconto']         : 0.0;

                if ($descManualLinha < 0) {
                    $descManualLinha = 0;
                }

                // PREÇO DE COMPRA QUE VAI FICAR GRAVADO = TABELA
                $precoCompra  = $precoTabelaCompra;

                // PREÇO DE REVENDA: se veio um valor > 0, usa o informado; senão, o da tabela
                $precoRevenda = ($precoImportadoRevenda && $precoImportadoRevenda > 0)
                    ? $precoImportadoRevenda
                    : $precoTabelaRevenda;

                // PONTOS: se informado, usa; senão, o da tabela
                $pontosUnit = ($pontosInformados !== null)
                    ? $pontosInformados
                    : $pontosTabela;

                // DESCONTO/Acréscimo AUTOMÁTICO (diferença entre tabela e importado)
                $descontoAuto = 0.0;

                if ($precoImportadoCompra && $precoImportadoCompra > 0) {
                    $descontoAuto = ($precoTabelaCompra - $precoImportadoCompra) * $qtd;
                }

                // Desconto manual nunca negativo:
                if ($descManualLinha < 0) {
                    $descManualLinha = 0;
                }

                // Desconto total da linha (pode ser negativo se importado > tabela)
                $valorDescontoLinha = $descManualLinha + $descontoAuto;

                // CÁLCULOS DE TOTAIS
                $totalItemCustoBruto = $qtd * $precoCompra;                      // preço tabela * qtd
                $totalItemLiquido    = max(0, $totalItemCustoBruto - $valorDescontoLinha); // custo final sem encargo
                $totalItemRevenda    = $qtd * $precoRevenda;
                $pontosLinha         = $qtd * $pontosUnit;

                // ACUMULA PARA O CABEÇALHO
                $totalBrutoCusto   += $totalItemCustoBruto;
                $totalDesconto     += $valorDescontoLinha;
                $totalLiquido      += $totalItemLiquido; // custo total sem encargos
                $totalRevenda      += $totalItemRevenda;
                $totalPontosGeral  += $pontosLinha;
                $qtditens++;

                // GUARDA PARA INSERIR EM appcompraproduto (sem encargos ainda)
                $itensCalc[] = [
                    'produto_id'      => $produtoId,
                    'quantidade'      => $qtd,
                    'preco_compra'    => $precoCompra,        // sempre o da tabela
                    'preco_revenda'   => $precoRevenda,       // pode ser informado
                    'total_custo'     => $totalItemCustoBruto,
                    'total_liquido'   => $totalItemLiquido,   // custo sem encargo
                    'total_revenda'   => $totalItemRevenda,
                    'pontos_unit'     => $pontosUnit,
                    'pontos_total'    => $pontosLinha,
                    'valor_desconto'  => $valorDescontoLinha, // manual + automático
                ];
            }

            // ==== RATEIO DOS ENCARGOS POR ITEM ====
            $valorCustoTotal = $totalLiquido; // custo total sem encargos

            if ($encargosCompra > 0 && $valorCustoTotal > 0 && count($itensCalc) > 0) {
                $restante   = $encargosCompra;
                $totalLinhas = count($itensCalc);

                foreach ($itensCalc as $i => &$it) {
                    if ($i === $totalLinhas - 1) {
                        // Última linha fica com o "resto" pra fechar centavos
                        $encargoLinha = $restante;
                    } else {
                        $proporcao    = $it['total_liquido'] / $valorCustoTotal;
                        $encargoLinha = round($encargosCompra * $proporcao, 2);
                        $restante    -= $encargoLinha;
                    }

                    $it['encargos']   = $encargoLinha;
                    $it['valorcusto'] = $it['total_liquido'];               // custo sem encargo
                    $it['final_liq']  = $it['total_liquido'] + $encargoLinha; // custo com encargo
                }
                unset($it);
            } else {
                // Sem encargos: valorcusto = total_liquido e final = total_liquido
                foreach ($itensCalc as &$it) {
                    $it['encargos']   = 0.0;
                    $it['valorcusto'] = $it['total_liquido'];
                    $it['final_liq']  = $it['total_liquido'];
                }
                unset($it);
            }

            $valorComEncargos = $valorCustoTotal + $encargosCompra;

            // ==== CABEÇALHO: tabela appcompra ====
            $compraId = DB::table('appcompra')->insertGetId([
                'fornecedor_id'      => $data['fornecedor_id'],
                'data_compra'        => $data['data_pedido'],
                'data_emissao'       => $data['data_entrega'] ?? null,
                'numpedcompra'       => null,
                'numero_nota'        => null,

                'valor_total'        => $totalBrutoCusto,
                'valor_desconto'     => $totalDesconto,

                'valorcusto'         => $valorCustoTotal,   // custo sem encargos
                'encargos'           => $encargosCompra,    // encargos financeiros
                'valor_liquido'      => $valorComEncargos,  // custo + encargos

                'preco_venda_total'  => $totalRevenda,
                'pontostotal'        => $totalPontosGeral,
                'qtditens'           => $qtditens,

                'forma_pagamento_id' => $data['forma_pagamento_id'] ?? null,
                'plano_pagamento_id' => $data['plano_pagamento_id'] ?? null,
                'qt_parcelas'        => $data['qt_parcelas'] ?? null,

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

                    'preco_unitario'       => $it['preco_compra'],       // tabela
                    'valor_desconto'       => $it['valor_desconto'],     // total da linha
                    'total_item'           => $it['total_custo'],        // bruto

                    'valorcusto'           => $it['valorcusto'],         // custo sem encargos
                    'encargos'             => $it['encargos'],           // encargo rateado
                    'total_liquido'        => $it['final_liq'],          // custo + encargo (final)

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
                ->with('success', 'Compra salva com sucesso (encargos financeiros rateados nos itens).');
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
        $formasPagamento  = FormaPagamento::orderBy('nome')->get();
        $planosPagamento  = PlanoPagamento::orderBy('descricao')->get();

        return view('compras.edit', [
            'pedido' => $pedido,
            'fornecedores' => $fornecedores,
            'formasPagamento'  => $formasPagamento,
            'planosPagamento'  => $planosPagamento,
            'produtos' => $produtos,
            'numeroPedido' => $pedido->numpedcompra ?? '(sem número)'
        ]);
    }

    /**
     * Atualiza a compra
     */
    public function update(Request $request, $id)
    {
        // Regra: não pode CONFIRMAR sem número do pedido e da nota
        if ($request->acao === 'confirmar') {
            $nroPedido = trim((string) $request->numpedcompra);
            $nroNota   = trim((string) $request->numero_nota);

            if ($nroPedido === '' || $nroNota === '') {
                return back()
                    ->withInput()
                    ->withErrors([
                        'numpedcompra' => 'Para confirmar o recebimento, informe o número do pedido.',
                        'numero_nota'  => 'Para confirmar o recebimento, informe o número da nota fiscal.',
                    ]);
            }
        }

        DB::beginTransaction();

        try {
            // Helper pra converter "1.234,56" em 1234.56
            $toFloat = function ($v) {
                if ($v === null || $v === '') {
                    return 0.0;
                }
                $v = str_replace('.', '', $v);   // milhares
                $v = str_replace(',', '.', $v);  // decimal
                return (float) $v;
            };

            $encargosCompra = $toFloat($request->input('encargos'));

            /** @var \App\Models\PedidoCompra $pedido */
            $pedido = PedidoCompra::with(['itens.produto'])->findOrFail($id);

            // guarda status antigo
            $jaRecebidaAntes = ($pedido->status === 'RECEBIDA');

            // --------- ATUALIZA CABEÇALHO (incluindo datas) ----------
            $pedido->fornecedor_id        = (int) $request->fornecedor_id;
            $pedido->numpedcompra         = $request->numpedcompra;
            $pedido->numero_nota          = $request->numero_nota;

            // campos de data vindos dos inputs da tela
            $pedido->data_compra          = $request->input('data_pedido')  ?: $pedido->data_compra;
            $pedido->data_emissao         = $request->input('data_entrega') ?: $pedido->data_emissao;

            // forma / plano / parcelas
            $pedido->forma_pagamento_id   = $request->input('forma_pagamento_id') ?: null;
            $pedido->plano_pagamento_id   = $request->input('plano_pagamento_id') ?: null;
            $pedido->qt_parcelas          = $request->input('qt_parcelas') ?: $pedido->qt_parcelas;

            // se ainda usa um campo texto "formapgto" antigo, mantém:
            $pedido->formapgto            = $request->input('formapgto', $pedido->formapgto);

            // observação
            $pedido->observacao           = $request->input('observacao');

            // ---------------------------------------------------------

            // pega itens do request
            $itensRequest = $request->input('itens', []);

            // Totais recalculados
            $valorTotalBruto  = 0;
            $valorDescontoTot = 0;
            $valorLiquidoTot  = 0; // custo sem encargos
            $valorVendaTot    = 0;
            $pontosTot        = 0;

            $linhas = []; // vamos guardar para ratear os encargos depois

            // Atualiza ITENS (primeiro passo: custo sem encargos)
            foreach ($itensRequest as $dados) {

                $itemId    = $dados['id']         ?? null;
                $produtoId = $dados['produto_id'] ?? null;

                if ($itemId) {
                    $item = $pedido->itens->where('id', $itemId)->first();
                } else {
                    $item = $pedido->itens->where('produto_id', $produtoId)->first();
                }

                if (! $item) {
                    continue;
                }

                $qtd           = $toFloat($dados['quantidade']    ?? $item->quantidade);
                $pontos        = $toFloat($dados['pontos']        ?? $item->pontos);
                $precoCompra   = $toFloat($dados['preco_compra']  ?? $item->preco_unitario);
                $precoRevenda  = $toFloat($dados['preco_revenda'] ?? $item->preco_venda_unitario);
                $descontoLinha = $toFloat($dados['desconto']      ?? ($item->valor_desconto ?? 0));

                if ($descontoLinha < 0) {
                    $descontoLinha = 0;
                }

                $totalItemBruto   = $qtd * $precoCompra;
                $totalItemLiquido = max(0, $totalItemBruto - $descontoLinha); // custo sem encargo
                $totalItemVenda   = $qtd * $precoRevenda;
                $pontosLinha      = $qtd * $pontos;

                $valorTotalBruto  += $totalItemBruto;
                $valorDescontoTot += $descontoLinha;
                $valorLiquidoTot  += $totalItemLiquido; // soma do custo sem encargos
                $valorVendaTot    += $totalItemVenda;
                $pontosTot        += $pontosLinha;

                $linhas[] = [
                    'model'            => $item,
                    'qtd'              => $qtd,
                    'pontos'           => $pontos,
                    'precoCompra'      => $precoCompra,
                    'precoRevenda'     => $precoRevenda,
                    'descontoLinha'    => $descontoLinha,
                    'totalItemBruto'   => $totalItemBruto,
                    'totalItemLiquido' => $totalItemLiquido,
                    'totalItemVenda'   => $totalItemVenda,
                    'pontosLinha'      => $pontosLinha,
                ];
            }

            // ==== RATEIO DOS ENCARGOS NAS LINHAS ====
            $valorCustoTotal = $valorLiquidoTot; // custo total sem encargos

            if ($encargosCompra > 0 && $valorCustoTotal > 0 && count($linhas) > 0) {
                $restante    = $encargosCompra;
                $totalLinhas = count($linhas);

                foreach ($linhas as $i => $linha) {
                    /** @var \App\Models\ItensCompra $item */
                    $item = $linha['model'];

                    if ($i === $totalLinhas - 1) {
                        $encargoLinha = $restante;
                    } else {
                        $proporcao    = $linha['totalItemLiquido'] / $valorCustoTotal;
                        $encargoLinha = round($encargosCompra * $proporcao, 2);
                        $restante    -= $encargoLinha;
                    }

                    $valorcusto = $linha['totalItemLiquido']; // sem encargo
                    $finalLiq   = $valorcusto + $encargoLinha; // com encargo

                    $item->update([
                        'quantidade'           => $linha['qtd'],
                        'qtd_disponivel'       => $linha['qtd'],
                        'preco_unitario'       => $linha['precoCompra'],
                        'valor_desconto'       => $linha['descontoLinha'],
                        'total_item'           => $linha['totalItemBruto'],

                        'valorcusto'           => $valorcusto,
                        'encargos'             => $encargoLinha,
                        'total_liquido'        => $finalLiq,

                        'preco_venda_unitario' => $linha['precoRevenda'],
                        'preco_venda_total'    => $linha['totalItemVenda'],
                        'pontos'               => $linha['pontos'],
                        'pontostotal'          => $linha['pontosLinha'],
                    ]);
                }
            } else {
                // Sem encargos: atualiza sem rateio
                foreach ($linhas as $linha) {
                    $item = $linha['model'];

                    $item->update([
                        'quantidade'           => $linha['qtd'],
                        'qtd_disponivel'       => $linha['qtd'],
                        'preco_unitario'       => $linha['precoCompra'],
                        'valor_desconto'       => $linha['descontoLinha'],
                        'total_item'           => $linha['totalItemBruto'],

                        'valorcusto'           => $linha['totalItemLiquido'],
                        'encargos'             => 0,
                        'total_liquido'        => $linha['totalItemLiquido'],

                        'preco_venda_unitario' => $linha['precoRevenda'],
                        'preco_venda_total'    => $linha['totalItemVenda'],
                        'pontos'               => $linha['pontos'],
                        'pontostotal'          => $linha['pontosLinha'],
                    ]);
                }
            }

            // Totais no cabeçalho
            $pedido->valor_total       = $valorTotalBruto;
            $pedido->valor_desconto    = $valorDescontoTot;
            $pedido->valorcusto        = $valorCustoTotal;                    // custo sem encargos
            $pedido->encargos          = $encargosCompra;                     // total de encargos
            $pedido->valor_liquido     = $valorCustoTotal + $encargosCompra;  // custo + encargos
            $pedido->preco_venda_total = $valorVendaTot;
            $pedido->pontostotal       = $pontosTot;

            // Status
            $pedido->status = $request->acao === 'confirmar' ? 'RECEBIDA' : 'PENDENTE';

            $pedido->save();

            // Se confirmar recebimento, movimenta estoque e gera contas a pagar
            if ($request->acao === 'confirmar' && ! $jaRecebidaAntes) {
                $estoqueService = new EstoqueService();
                $estoqueService->registrarEntradaCompra($pedido);

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

            Log::error('Erro ao atualizar pedido de compra', [
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

            $codigo       = trim($linha[0] ?? '');
            $qtd          = (float) str_replace(',', '.', $linha[1] ?? 0);
            $pontos       = isset($linha[2]) ? (float) str_replace(',', '.', $linha[2]) : 0;
            $precoCompra  = isset($linha[3]) ? (float) str_replace(',', '.', $linha[3]) : 0;
            $precoRevenda = isset($linha[4]) ? (float) str_replace(',', '.', $linha[4]) : 0;

            if ($codigo === '' || $qtd <= 0) {
                continue;
            }

            $produto = Produto::where('codfab', $codigo)
                ->orWhere('codfabnumero', $codigo)
                ->first();

            if (!$produto) {
                continue;
            }

            ItensCompra::create([
                'compra_id'            => $pedido->id,
                'produto_id'           => $produto->id,
                'quantidade'           => $qtd,
                'qtd_disponivel'       => $qtd,
                'preco_unitario'       => $precoCompra,
                'valor_desconto'       => 0,
                'total_item'           => $qtd * $precoCompra,
                'valorcusto'           => $qtd * $precoCompra, // sem encargo (import)
                'encargos'             => 0,
                'total_liquido'        => $qtd * $precoCompra, // custo final = sem encargo
                'pontos'               => $pontos,
                'pontostotal'          => $qtd * $pontos,
                'preco_venda_unitario' => $precoRevenda,
                'preco_venda_total'    => $qtd * $precoRevenda,
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
     * Remove um pedido (na verdade cancela, não exclui)
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

            // REGRA 2 – Se tiver qualquer parcela com baixa (paga), não pode cancelar
            $parcelasComBaixa = $pedido->contasPagar
                ->filter(fn($c) => $c->baixas->isNotEmpty());

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

            // REGRA 1 – Verificar se o estorno do estoque deixaria algum item negativo (se compra RECEBIDA)
            $itensComEstoqueNegativo = [];

            if ($pedido->status === 'RECEBIDA') {
                foreach ($pedido->itens as $item) {
                    $produto = $item->produto;

                    $viewProd = null;
                    if ($produto && $produto->codfabnumero) {
                        $viewProd = ViewProduto::where('codigo_fabrica', $produto->codfabnumero)->first();
                    }

                    $estoqueAtual = $viewProd->qtd_estoque ?? 0;
                    $qtdEstornar  = (float) $item->quantidade;

                    $saldoPosEstorno = $estoqueAtual - $qtdEstornar;

                    if ($saldoPosEstorno < 0) {
                        $itensComEstoqueNegativo[] = [
                            'produto'          => $produto->nome ?? ('ID ' . $produto->id),
                            'codigo_fabrica'   => $produto->codfabnumero ?? null,
                            'estoque_atual'    => $estoqueAtual,
                            'qtd_estornar'     => $qtdEstornar,
                            'saldo_pos_estorno' => $saldoPosEstorno,
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

            // REGRA 3 – Cancelamento permitido
            // - Estorna estoque (se RECEBIDA)
            // - Estorna/cancela parcelas de Contas a Pagar
            // - Não exclui o pedido nem os itens — apenas marca como CANCELADA

            if ($pedido->status === 'RECEBIDA') {
                $service = new EstoqueService();
                $service->estornarEntradaCompra($pedido, $motivo);
            }

            // Cancelar/estornar parcelas de Contas a Pagar
            foreach ($pedido->contasPagar as $conta) {

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

            // Atualiza o pedido para CANCELADA
            $textoObsPedido = trim(
                ($pedido->observacao ? $pedido->observacao . "\n" : '') .
                    'Pedido cancelado. Motivo: ' . $motivo
            );

            $pedido->status     = 'CANCELADA';
            $pedido->observacao = $textoObsPedido;
            $pedido->save();

            DB::commit();

            return redirect()
                ->route('compras.index')
                ->with('success', 'Pedido cancelado com sucesso. Estoque e parcelas de Contas a Pagar foram estornados.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao cancelar pedido: ' . $e->getMessage());
        }
    }

    private function brToFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $value = trim((string) $value);

        $hasComma = strpos($value, ',') !== false;
        $hasDot   = strpos($value, '.') !== false;

        // Caso 1: só vírgula  → formato BR: 1.234,56
        if ($hasComma && ! $hasDot) {
            // remove possíveis milhares e troca vírgula por ponto
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // Caso 2: só ponto → vamos assumir que o ponto é decimal (11.81)
        if ($hasDot && ! $hasComma) {
            return (float) $value;
        }

        // Caso 3: tem ponto e vírgula → típico "1.234,56"
        if ($hasDot && $hasComma) {
            // assume . como milhar e , como decimal
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // Caso 4: só dígitos (ex: "1181")
        return (float) $value;
    }
}
