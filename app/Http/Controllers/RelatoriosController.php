<?php

namespace App\Http\Controllers;

use App\Models\PedidoVenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatoriosController extends Controller
{
    public function rentabilidadeVendas(Request $request)
    {
        // Empresa atual (mesma lógica que você usa no sidebar)
        $empresaId = $request->user()->empresa_id ?? null;

        if (!$empresaId && app()->bound('empresa')) {
            $empresaId = app('empresa')->id ?? null;
        }

        // Filtros
        $de  = $request->input('de', now()->subDays(30)->toDateString());
        $ate = $request->input('ate', now()->toDateString());

        $status    = $request->input('status', 'TODOS'); // TODOS | PENDENTE | ABERTO | ENTREGUE | CANCELADO ...
        $baseCusto = $request->input('base_custo', 'produto'); // produto | estoque

        // Expressão de custo por item
        // - produto: appproduto.preco_compra
        // - estoque: appestoque.ultimo_preco_compra (por empresa) com fallback para preco_compra
        $custoUnitExpr = $baseCusto === 'estoque'
            ? 'COALESCE(es.ultimo_preco_compra, p.preco_compra, 0)'
            : 'COALESCE(p.preco_compra, 0)';

        // Subquery: custo por pedido (somando itens)
        $itensSub = DB::table('appitemvenda as iv')
            ->join('appproduto as p', 'p.id', '=', 'iv.produto_id')
            ->leftJoin('appestoque as es', function ($join) use ($empresaId) {
                $join->on('es.produto_id', '=', 'iv.produto_id');
                if ($empresaId) {
                    $join->where('es.empresa_id', '=', $empresaId);
                }
            })
            ->selectRaw('iv.pedido_id as pedido_id')
            ->selectRaw('SUM(COALESCE(iv.quantidade,0) * ' . $custoUnitExpr . ') as custo_total')
            ->selectRaw('SUM(COALESCE(iv.quantidade,0)) as qtd_total')
            ->selectRaw('COUNT(*) as qtd_linhas')
            ->groupBy('iv.pedido_id');

        // Base query (1 linha por pedido)
        $baseQuery = DB::table('apppedidovenda as pv')
            ->leftJoin('appcliente as c', 'c.id', '=', 'pv.cliente_id')
            ->leftJoin('apprevendedora as r', 'r.id', '=', 'pv.revendedora_id')
            ->leftJoinSub($itensSub, 'it', function ($join) {
                $join->on('it.pedido_id', '=', 'pv.id');
            })
            ->when($empresaId, fn($q) => $q->where('pv.empresa_id', $empresaId))
            ->whereDate('pv.data_pedido', '>=', $de)
            ->whereDate('pv.data_pedido', '<=', $ate)
            ->when($status !== 'TODOS', fn($q) => $q->where('pv.status', $status));

        // Totais (resumo)
        $totais = (clone $baseQuery)
            ->selectRaw('
                SUM(COALESCE(pv.valor_total,0))   as receita_bruta,
                SUM(COALESCE(pv.valor_desconto,0)) as desconto_total,
                SUM(COALESCE(pv.valor_liquido,0)) as receita_liquida,
                SUM(COALESCE(it.custo_total,0))   as custo_total
            ')
            ->first();

        $receitaLiquida = (float)($totais->receita_liquida ?? 0);
        $custoTotal     = (float)($totais->custo_total ?? 0);
        $lucroTotal     = $receitaLiquida - $custoTotal;
        $margemTotal    = $receitaLiquida > 0 ? ($lucroTotal / $receitaLiquida) * 100 : 0;

        // Lista detalhada
        $vendas = (clone $baseQuery)
            ->select([
                'pv.id',
                'pv.data_pedido',
                'pv.status',
                'pv.valor_total',
                'pv.valor_desconto',
                'pv.valor_liquido',
                'pv.forma_pagamento_id',
                'pv.plano_pagamento_id',
                'c.nome as cliente_nome',
                'r.nome as revendedora_nome',
            ])
            ->selectRaw('COALESCE(it.custo_total,0) as custo_total')
            ->selectRaw('(COALESCE(pv.valor_liquido,0) - COALESCE(it.custo_total,0)) as lucro')
            ->selectRaw('CASE WHEN COALESCE(pv.valor_liquido,0) > 0
                        THEN ((COALESCE(pv.valor_liquido,0) - COALESCE(it.custo_total,0)) / COALESCE(pv.valor_liquido,0)) * 100
                        ELSE 0 END as margem_perc')
            ->orderByDesc('pv.data_pedido')
            ->orderByDesc('pv.id')
            ->paginate(30)
            ->withQueryString();

        return view('relatorios.rentabilidade-vendas', [
            'vendas' => $vendas,
            'filtros' => [
                'de' => $de,
                'ate' => $ate,
                'status' => $status,
                'base_custo' => $baseCusto,
            ],
            'totais' => [
                'receita_bruta'   => (float)($totais->receita_bruta ?? 0),
                'desconto_total'  => (float)($totais->desconto_total ?? 0),
                'receita_liquida' => $receitaLiquida,
                'custo_total'     => $custoTotal,
                'lucro_total'     => $lucroTotal,
                'margem_total'    => $margemTotal,
            ],
        ]);
    }

    public function vendasDetalhadas(Request $request)
    {
        // Empresa atual
        $empresaId = $request->user()->empresa_id ?? null;
        if (!$empresaId && app()->bound('empresa')) {
            $empresaId = app('empresa')->id ?? null;
        }

        // Filtros
        $de  = $request->input('de', now()->subDays(30)->toDateString());
        $ate = $request->input('ate', now()->toDateString());
        $status = $request->input('status', 'TODOS');

        // Query base (pedidos)
        $q = PedidoVenda::query()
            ->when($empresaId, fn($qq) => $qq->where('empresa_id', $empresaId))
            ->whereDate('data_pedido', '>=', $de)
            ->whereDate('data_pedido', '<=', $ate)
            ->when($status !== 'TODOS', fn($qq) => $qq->where('status', $status))
            ->with([
                'cliente',
                'revendedora',
                'forma',
                'plano',
                'itens.produto',
            ])
            ->orderByDesc('data_pedido')
            ->orderByDesc('id');

        $pedidos = $q->paginate(15)->withQueryString();

        // Coletar produtos da página para buscar última compra em lote
        $productIds = collect($pedidos->items())
            ->flatMap(fn($p) => $p->itens->pluck('produto_id'))
            ->filter()->unique()->values();

        $mapUltCompra = collect();
        if ($empresaId && $productIds->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, $productIds->count(), '?'));

            $sql = "
                SELECT produto_id, ultima_qtd, ultima_data
                FROM (
                    SELECT
                        cp.produto_id,
                        cp.quantidade AS ultima_qtd,
                        COALESCE(c.data_compra, c.data_emissao, c.created_at) AS ultima_data,
                        ROW_NUMBER() OVER (
                            PARTITION BY cp.produto_id
                            ORDER BY COALESCE(c.data_compra, c.data_emissao, c.created_at) DESC, c.id DESC, cp.id DESC
                        ) AS rn
                    FROM appcompraproduto cp
                    JOIN appcompra c ON c.id = cp.compra_id
                    WHERE c.empresa_id = ?
                      AND cp.produto_id IN ($placeholders)
                ) t
                WHERE rn = 1
            ";

            $rows = DB::select($sql, array_merge([$empresaId], $productIds->all()));
            $mapUltCompra = collect($rows)->keyBy('produto_id');
        }

        // Calcular rentabilidade/pontos por pedido + injetar ultima compra nos itens
        foreach ($pedidos as $pedido) {
            $itens = $pedido->itens ?? collect();

            foreach ($itens as $it) {
                $row = $mapUltCompra->get($it->produto_id);
                $it->ultima_compra_qtd  = $row->ultima_qtd  ?? null;
                $it->ultima_compra_data = $row->ultima_data ?? null;
            }

            $receitaLiquidaItens = (float) $itens->sum(
                fn($it) =>
                (float)($it->preco_total ?? 0) - (float)($it->valor_desconto ?? 0)
            );

            $custoTotal = (float) $itens->sum(
                fn($it) =>
                (float)($it->quantidade ?? 0) * (float)($it->produto->preco_compra ?? 0)
            );

            $lucro = $receitaLiquidaItens - $custoTotal;
            $margem = $receitaLiquidaItens > 0 ? ($lucro / $receitaLiquidaItens) * 100 : 0;

            $pedido->calc = [
                'receita_liquida_itens' => $receitaLiquidaItens,
                'custo_total' => $custoTotal,
                'lucro' => $lucro,
                'margem' => $margem,
                'pontos' => (int) $itens->sum('pontuacao'),
                'pontos_total' => (int) $itens->sum('pontuacao_total'),
                'qtd_itens' => (int) $itens->sum('quantidade'),
            ];
        }

        // Totais do período (geral)
        $totPedidos = DB::table('apppedidovenda as pv')
            ->when($empresaId, fn($qq) => $qq->where('pv.empresa_id', $empresaId))
            ->whereDate('pv.data_pedido', '>=', $de)
            ->whereDate('pv.data_pedido', '<=', $ate)
            ->when($status !== 'TODOS', fn($qq) => $qq->where('pv.status', $status))
            ->selectRaw('COUNT(*) as qtd_pedidos')
            ->selectRaw('SUM(COALESCE(pv.valor_liquido,0)) as receita_liquida')
            ->selectRaw('SUM(COALESCE(pv.valor_desconto,0)) as desconto_total')
            ->first();

        $totItens = DB::table('apppedidovenda as pv')
            ->join('appitemvenda as iv', 'iv.pedido_id', '=', 'pv.id')
            ->leftJoin('appproduto as p', 'p.id', '=', 'iv.produto_id')
            ->when($empresaId, fn($qq) => $qq->where('pv.empresa_id', $empresaId))
            ->whereDate('pv.data_pedido', '>=', $de)
            ->whereDate('pv.data_pedido', '<=', $ate)
            ->when($status !== 'TODOS', fn($qq) => $qq->where('pv.status', $status))
            ->selectRaw('SUM(COALESCE(iv.quantidade,0) * COALESCE(p.preco_compra,0)) as custo_total')
            ->selectRaw('SUM(COALESCE(iv.pontuacao,0)) as pontos')
            ->selectRaw('SUM(COALESCE(iv.pontuacao_total,0)) as pontos_total')
            ->first();

        $receitaLiquida = (float)($totPedidos->receita_liquida ?? 0);
        $custoTotal = (float)($totItens->custo_total ?? 0);
        $lucroTotal = $receitaLiquida - $custoTotal;
        $margemTotal = $receitaLiquida > 0 ? ($lucroTotal / $receitaLiquida) * 100 : 0;

        $totais = [
            'qtd_pedidos' => (int)($totPedidos->qtd_pedidos ?? 0),
            'receita_liquida' => $receitaLiquida,
            'desconto_total' => (float)($totPedidos->desconto_total ?? 0),
            'custo_total' => $custoTotal,
            'lucro_total' => $lucroTotal,
            'margem_total' => $margemTotal,
            'pontos' => (int)($totItens->pontos ?? 0),
            'pontos_total' => (int)($totItens->pontos_total ?? 0),
        ];

        return view('relatorios.vendas-detalhadas', [
            'pedidos' => $pedidos,
            'filtros' => compact('de', 'ate', 'status'),
            'totais' => $totais,
        ]);
    }
    public function entradaMercadoria(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        if (!$empresaId && app()->bound('empresa')) {
            $empresaId = app('empresa')->id ?? null;
        }

        $de  = $request->input('de', now()->subDays(30)->toDateString());
        $ate = $request->input('ate', now()->toDateString());

        $produto = trim((string) $request->input('produto', ''));

        $dataExpr = "DATE(COALESCE(c.data_compra, c.data_emissao, c.created_at))";

        // Base (linhas agregadas por DIA + PRODUTO)
        $q = DB::table('appcompraproduto as cp')
            ->join('appcompra as c', 'c.id', '=', 'cp.compra_id')
            ->leftJoin('appproduto as p', 'p.id', '=', 'cp.produto_id')
            ->leftJoin('appfornecedor as f', 'f.id', '=', 'c.fornecedor_id')
            ->when($empresaId, fn($qq) => $qq->where('c.empresa_id', $empresaId))
            ->whereRaw("$dataExpr >= ?", [$de])
            ->whereRaw("$dataExpr <= ?", [$ate])
            ->when($produto !== '', function ($qq) use ($produto) {
                $qq->where(function ($w) use ($produto) {
                    $w->where('p.codfabnumero', 'like', '%' . $produto . '%')
                        ->orWhere('p.nome', 'like', '%' . $produto . '%');
                });
            })
            ->selectRaw("$dataExpr as data_entrada")
            ->selectRaw("cp.produto_id")
            ->selectRaw("COALESCE(p.nome, CONCAT('Produto #', cp.produto_id)) as produto_nome")
            ->selectRaw("COALESCE(p.codfabnumero, '') as codfabnumero")
            ->selectRaw("SUM(COALESCE(cp.quantidade,0)) as qtd_total")
            ->selectRaw("SUM(COALESCE(cp.total_item, cp.total_liquido, (COALESCE(cp.quantidade,0) * COALESCE(cp.preco_unitario,0)))) as valor_total_compra")
            ->selectRaw("
            CASE
              WHEN SUM(COALESCE(cp.quantidade,0)) > 0
              THEN SUM(COALESCE(cp.total_item, cp.total_liquido, (COALESCE(cp.quantidade,0) * COALESCE(cp.preco_unitario,0))))
                   / SUM(COALESCE(cp.quantidade,0))
              ELSE 0
            END as preco_medio_unit
        ")
            ->selectRaw("GROUP_CONCAT(DISTINCT COALESCE(c.numpedcompra,'') SEPARATOR ' | ') as numpedcompras")
            ->selectRaw("GROUP_CONCAT(DISTINCT COALESCE(c.numero_nota,'') SEPARATOR ' | ') as numero_notas")
            ->selectRaw("GROUP_CONCAT(DISTINCT COALESCE(f.nomefantasia, f.razaosocial) SEPARATOR ' | ') as fornecedores")
            ->groupByRaw("$dataExpr, cp.produto_id, produto_nome, codfabnumero")
            ->orderBy('data_entrada')
            ->orderBy('produto_nome');

        // ✅ Totais do período (não usa clone do select agrupado)
        $totais = DB::table('appcompraproduto as cp')
            ->join('appcompra as c', 'c.id', '=', 'cp.compra_id')
            ->leftJoin('appproduto as p', 'p.id', '=', 'cp.produto_id')
            ->when($produto !== '', function ($qq) use ($produto) {
                $qq->where(function ($w) use ($produto) {
                    $w->where('p.codfabnumero', 'like', '%' . $produto . '%')
                        ->orWhere('p.nome', 'like', '%' . $produto . '%');
                });
            })
            ->when($empresaId, fn($qq) => $qq->where('c.empresa_id', $empresaId))
            ->whereRaw("$dataExpr >= ?", [$de])
            ->whereRaw("$dataExpr <= ?", [$ate])
            ->selectRaw("SUM(COALESCE(cp.quantidade,0)) as qtd_total_geral")
            ->selectRaw("SUM(COALESCE(cp.total_item, cp.total_liquido, (COALESCE(cp.quantidade,0) * COALESCE(cp.preco_unitario,0)))) as valor_total_geral")
            ->first();

        $linhas = $q->paginate(30)->withQueryString();

        return view('relatorios.entrada-mercadoria', [
            'linhas' => $linhas,
            'filtros' => compact('de', 'ate', 'produto'),
            'totais' => [
                'qtd_total_geral' => (float)($totais->qtd_total_geral ?? 0),
                'valor_total_geral' => (float)($totais->valor_total_geral ?? 0),
            ],
        ]);
    }
}
