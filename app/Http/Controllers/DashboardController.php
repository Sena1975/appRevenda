<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Produto;
use App\Models\ContasReceber;
use App\Models\PedidoVenda;
use App\Models\PedidoCompra;
use App\Models\Estoque;
use App\Models\Indicacao;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totClientes = Cliente::count();

        /*
        |--------------------------------------------------------------------------
        | PRODUTOS / ESTOQUE
        |--------------------------------------------------------------------------
        */
        $produtoTable = (new Produto())->getTable();   // normalmente "appproduto"
        $estoqueTable = (new Estoque())->getTable();   // normalmente "appestoque"

        // Detecta coluna de quantidade no estoque
        $valorPremiosPendentes = Indicacao::where('status', 'pendente')->sum('valor_premio');
        $valorPremiosPagos     = Indicacao::where('status', 'pago')->sum('valor_premio');

        // Detecta coluna de quantidade no estoque
        $colQtd = Schema::hasColumn($estoqueTable, 'disponivel') ? 'disponivel'
            : (Schema::hasColumn($estoqueTable, 'quantidade') ? 'quantidade'
                : (Schema::hasColumn($estoqueTable, 'qtd_estoque') ? 'qtd_estoque' : null));

        // Detecta coluna de preço em appproduto
        $colPreco = Schema::hasColumn($produtoTable, 'preco_compra') ? 'preco_compra'
            : (Schema::hasColumn($produtoTable, 'preco_revenda') ? 'preco_revenda' : null);

        // Quantidade de produtos com estoque > 0 (contando produtos distintos)
        if ($colQtd) {
            $totProdutosEstoque = Estoque::where($colQtd, '>', 0)
                ->distinct('produto_id')
                ->count('produto_id');
        } else {
            $totProdutosEstoque = 0;
        }

        // Valor do estoque = SUM(qtd * preço)
        $valorEstoque = 0;
        if ($colQtd && $colPreco) {
            $valorEstoque = (float) (
                DB::table($estoqueTable . ' as e')
                ->join($produtoTable . ' as p', 'p.id', '=', 'e.produto_id')
                ->where('e.' . $colQtd, '>', 0)
                ->selectRaw("SUM(e.$colQtd * p.$colPreco) as total")
                ->value('total') ?? 0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CONTAS A RECEBER EM ABERTO
        |--------------------------------------------------------------------------
        */
        $query = ContasReceber::query();
        $crTable = (new ContasReceber())->getTable();

        // 1) status = 'aberto'
        if (Schema::hasColumn($crTable, 'status')) {
            $query->where('status', 'aberto');
        }
        // 2) pago = 0
        elseif (Schema::hasColumn($crTable, 'pago')) {
            $query->where('pago', 0);
        }
        // 3) data_baixa IS NULL
        elseif (Schema::hasColumn($crTable, 'data_baixa')) {
            $query->whereNull('data_baixa');
        }

        // Nome da coluna do valor
        $colValor = Schema::hasColumn($crTable, 'valor') ? 'valor'
            : (Schema::hasColumn($crTable, 'valor_titulo') ? 'valor_titulo' : null);

        $crEmAberto  = (clone $query)->count();
        $valorAberto = $colValor ? (clone $query)->sum($colValor) : 0;

        /*
        |--------------------------------------------------------------------------
        | COMPRAS E VENDAS (MÊS ATUAL)
        |--------------------------------------------------------------------------
        */
        $inicioMes = Carbon::now()->startOfMonth();
        $hoje      = Carbon::now()->endOfDay();

        // VENDAS do mês
        $vendasMesQuery = PedidoVenda::whereBetween('data_pedido', [$inicioMes, $hoje]);
        $totVendasMes   = (clone $vendasMesQuery)->count();
        $faturamentoMes = (clone $vendasMesQuery)->sum('valor_liquido');

        // COMPRAS do mês
        $comprasMesQuery = PedidoCompra::whereBetween('data_compra', [$inicioMes, $hoje]);
        $totComprasMes   = (clone $comprasMesQuery)->count();
        $valorComprasMes = (clone $comprasMesQuery)->sum('valor_total');

        /*
        |--------------------------------------------------------------------------
        | PENDENTES (SEM FILTRO DE DATA)
        |--------------------------------------------------------------------------
        */
        // Vendas com status PENDENTE
        $vendasPendentesQuery   = PedidoVenda::where('status', 'PENDENTE');
        $totVendasPendentes     = (clone $vendasPendentesQuery)->count();
        $valorVendasPendentes   = (clone $vendasPendentesQuery)->sum('valor_liquido');

        // Compras com status PENDENTE
        $comprasPendentesQuery  = PedidoCompra::where('status', 'PENDENTE');
        $totComprasPendentes    = (clone $comprasPendentesQuery)->count();
        $valorComprasPendentes  = (clone $comprasPendentesQuery)->sum('valor_total');

        /*
        |--------------------------------------------------------------------------
        | VISÃO RÁPIDA: últimas vendas/compras (lista)
        |--------------------------------------------------------------------------
        */
        $ultimasVendas = PedidoVenda::orderByDesc('data_pedido')
            ->limit(5)
            ->get(['id', 'data_pedido', 'valor_liquido']);

        $ultimasCompras = PedidoCompra::orderByDesc('data_compra')
            ->limit(5)
            ->get(['id', 'data_compra', 'valor_total']);

        /*
        |--------------------------------------------------------------------------
        | GRÁFICO DE EVOLUÇÃO - ÚLTIMAS COMPRAS
        | Agrupa as compras por dia (R$ total/dia) nos últimos X dias
        |--------------------------------------------------------------------------
        */
        $periodoEvolucaoDias = 180; // ajuste se quiser mais/menos dias

        $inicioPeriodo = Carbon::now()
            ->subDays($periodoEvolucaoDias)
            ->startOfDay();

        $comprasEvolucao = PedidoCompra::selectRaw('DATE(data_compra) as dia, SUM(valor_total) as total')
            ->whereBetween('data_compra', [$inicioPeriodo, $hoje])
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // Labels (eixo X) = datas formatadas
        $comprasEvolucaoLabels = $comprasEvolucao->map(function ($row) {
            return Carbon::parse($row->dia)->format('d/m');
        });

        // Valores (eixo Y) = total de compras no dia
        $comprasEvolucaoValores = $comprasEvolucao->pluck('total');

        return view('dashboard', [
            'totClientes'                 => $totClientes,
            'totProdutosEstoque'          => $totProdutosEstoque,
            'valorEstoque'                => $valorEstoque,
            'crEmAberto'                  => $crEmAberto,
            'valorAberto'                 => $valorAberto,
            'totVendasMes'                => $totVendasMes,
            'faturamentoMes'              => $faturamentoMes,
            'totComprasMes'               => $totComprasMes,
            'valorComprasMes'             => $valorComprasMes,
            'ultimasVendas'               => $ultimasVendas,
            'ultimasCompras'              => $ultimasCompras,
            'totVendasPendentes'          => $totVendasPendentes,
            'valorVendasPendentes'        => $valorVendasPendentes,
            'totComprasPendentes'         => $totComprasPendentes,
            'valorPremiosPendentes' => $valorPremiosPendentes,
            'valorPremiosPagos'     => $valorPremiosPagos,
            'valorComprasPendentes'       => $valorComprasPendentes,
            'comprasEvolucaoLabels'       => $comprasEvolucaoLabels,
            'comprasEvolucaoValores'      => $comprasEvolucaoValores,
            'periodoEvolucaoComprasDias'  => $periodoEvolucaoDias,
        ]);
    }
}
