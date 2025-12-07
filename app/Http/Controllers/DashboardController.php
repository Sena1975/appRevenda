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
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $empresaId = $user->empresa_id;

        /*
        |--------------------------------------------------------------------------|
        | CLIENTES
        |--------------------------------------------------------------------------|
        */
        // Só clientes da empresa atual
        $totClientes = Cliente::where('empresa_id', $empresaId)->count();

        /*
        |--------------------------------------------------------------------------|
        | PRODUTOS / ESTOQUE
        |--------------------------------------------------------------------------|
        */
        $produtoTable = (new Produto())->getTable();   // normalmente "appproduto"
        $estoqueTable = (new Estoque())->getTable();   // normalmente "appestoque"

        // INDICAÇÕES (premiação) filtradas por empresa se existir empresa_id na tabela
        $indicacaoTable = (new Indicacao())->getTable();
        $indicacaoBase  = Indicacao::query();

        if (Schema::hasColumn($indicacaoTable, 'empresa_id')) {
            $indicacaoBase->where('empresa_id', $empresaId);
        }

        $valorPremiosPendentes = (clone $indicacaoBase)
            ->where('status', 'pendente')
            ->sum('valor_premio');

        $valorPremiosPagos = (clone $indicacaoBase)
            ->where('status', 'pago')
            ->sum('valor_premio');

        // Detecta coluna de quantidade no estoque
        $colQtd = Schema::hasColumn($estoqueTable, 'disponivel') ? 'disponivel'
            : (Schema::hasColumn($estoqueTable, 'quantidade') ? 'quantidade'
                : (Schema::hasColumn($estoqueTable, 'qtd_estoque') ? 'qtd_estoque' : null));

        // Detecta coluna de preço em appproduto
        $colPreco = Schema::hasColumn($produtoTable, 'preco_compra') ? 'preco_compra'
            : (Schema::hasColumn($produtoTable, 'preco_revenda') ? 'preco_revenda' : null);

        // Base de consulta de estoque (se tiver empresa_id em estoque, filtra por ele;
        // senão, se tiver empresa_id em produto, filtra lá)
        $estoqueBase = Estoque::query();
        $estoqueTemEmpresa = Schema::hasColumn($estoqueTable, 'empresa_id');
        $produtoTemEmpresa = Schema::hasColumn($produtoTable, 'empresa_id');

        if ($estoqueTemEmpresa) {
            $estoqueBase->where('empresa_id', $empresaId);
        }

        // Quantidade de produtos com estoque > 0 (contando produtos distintos)
        if ($colQtd) {
            $q = (clone $estoqueBase)->where($colQtd, '>', 0);
            $totProdutosEstoque = $q->distinct('produto_id')->count('produto_id');
        } else {
            $totProdutosEstoque = 0;
        }

        // Valor do estoque = SUM(qtd * preço)
        $valorEstoque = 0;
        if ($colQtd && $colPreco) {
            $estoqueJoin = DB::table($estoqueTable . ' as e')
                ->join($produtoTable . ' as p', 'p.id', '=', 'e.produto_id')
                ->where('e.' . $colQtd, '>', 0);

            if ($estoqueTemEmpresa) {
                $estoqueJoin->where('e.empresa_id', $empresaId);
            } elseif ($produtoTemEmpresa) {
                $estoqueJoin->where('p.empresa_id', $empresaId);
            }

            $valorEstoque = (float) ($estoqueJoin
                ->selectRaw("SUM(e.$colQtd * p.$colPreco) as total")
                ->value('total') ?? 0);
        }

        /*
        |--------------------------------------------------------------------------|
        | CONTAS A RECEBER EM ABERTO (opcionalmente multiempresa)
        |--------------------------------------------------------------------------|
        */
        $crTable = (new ContasReceber())->getTable();

        $queryCR = ContasReceber::query();

        // Se a tabela appcontasreceber já tiver empresa_id, filtra por ele
        if (Schema::hasColumn($crTable, 'empresa_id')) {
            $queryCR->where('empresa_id', $empresaId);
        }

        // 1) status = 'aberto'
        if (Schema::hasColumn($crTable, 'status')) {
            $queryCR->where('status', 'aberto');
        }
        // 2) pago = 0
        elseif (Schema::hasColumn($crTable, 'pago')) {
            $queryCR->where('pago', 0);
        }
        // 3) data_baixa IS NULL
        elseif (Schema::hasColumn($crTable, 'data_baixa')) {
            $queryCR->whereNull('data_baixa');
        }

        // Nome da coluna do valor
        $colValor = Schema::hasColumn($crTable, 'valor') ? 'valor'
            : (Schema::hasColumn($crTable, 'valor_titulo') ? 'valor_titulo' : null);

        $crEmAberto  = (clone $queryCR)->count();
        $valorAberto = $colValor ? (clone $queryCR)->sum($colValor) : 0;

        /*
        |--------------------------------------------------------------------------|
        | COMPRAS E VENDAS (MÊS ATUAL) - SEMPRE POR EMPRESA
        |--------------------------------------------------------------------------|
        */
        $inicioMes = Carbon::now()->startOfMonth();
        $hoje      = Carbon::now()->endOfDay();

        // VENDAS do mês (da empresa)
        $vendasMesQuery = PedidoVenda::where('empresa_id', $empresaId)
            ->whereBetween('data_pedido', [$inicioMes, $hoje]);

        $totVendasMes   = (clone $vendasMesQuery)->count();
        $faturamentoMes = (clone $vendasMesQuery)->sum('valor_liquido');

        // COMPRAS do mês (da empresa)
        $comprasMesQuery = PedidoCompra::where('empresa_id', $empresaId)
            ->whereBetween('data_compra', [$inicioMes, $hoje]);

        $totComprasMes   = (clone $comprasMesQuery)->count();
        $valorComprasMes = (clone $comprasMesQuery)->sum('valor_total');

        /*
        |--------------------------------------------------------------------------|
        | PENDENTES (SEM FILTRO DE DATA) - POR EMPRESA
        |--------------------------------------------------------------------------|
        */
        // Vendas com status PENDENTE
        $vendasPendentesQuery = PedidoVenda::where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE');

        $totVendasPendentes   = (clone $vendasPendentesQuery)->count();
        $valorVendasPendentes = (clone $vendasPendentesQuery)->sum('valor_liquido');

        // Compras com status PENDENTE
        $comprasPendentesQuery = PedidoCompra::where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE');

        $totComprasPendentes   = (clone $comprasPendentesQuery)->count();
        $valorComprasPendentes = (clone $comprasPendentesQuery)->sum('valor_total');

        /*
        |--------------------------------------------------------------------------|
        | VISÃO RÁPIDA: últimas vendas/compras (lista) - POR EMPRESA
        |--------------------------------------------------------------------------|
        */
        $ultimasVendas = PedidoVenda::where('empresa_id', $empresaId)
            ->orderByDesc('data_pedido')
            ->limit(5)
            ->get(['id', 'data_pedido', 'valor_liquido']);

        $ultimasCompras = PedidoCompra::where('empresa_id', $empresaId)
            ->orderByDesc('data_compra')
            ->limit(5)
            ->get(['id', 'data_compra', 'valor_total']);

        /*
        |--------------------------------------------------------------------------|
        | GRÁFICO DE EVOLUÇÃO - ÚLTIMAS COMPRAS (POR EMPRESA)
        | Agrupa as compras por dia (R$ total/dia) nos últimos X dias
        |--------------------------------------------------------------------------|
        */
        $periodoEvolucaoDias = 180; // ajuste se quiser mais/menos dias

        $inicioPeriodo = Carbon::now()
            ->subDays($periodoEvolucaoDias)
            ->startOfDay();

        $comprasEvolucao = PedidoCompra::where('empresa_id', $empresaId)
            ->selectRaw('DATE(data_compra) as dia, SUM(valor_total) as total')
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
            'valorComprasPendentes'       => $valorComprasPendentes,
            'valorPremiosPendentes'       => $valorPremiosPendentes,
            'valorPremiosPagos'           => $valorPremiosPagos,
            'comprasEvolucaoLabels'       => $comprasEvolucaoLabels,
            'comprasEvolucaoValores'      => $comprasEvolucaoValores,
            'periodoEvolucaoComprasDias'  => $periodoEvolucaoDias,
        ]);
    }
}
