<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ContasPagar; // já existe no seu projeto
use Carbon\Carbon;

class RelatorioFinanceiroController extends Controller
{
    /**
     * Previsão de recebimentos usando a view_app_contasreceber.
     * Agrupa por data de vencimento, somando o SALDO (não o valor original).
     */
    public function previsaoRecebimentos(Request $request)
    {
        $hoje = Carbon::today();

        $inicio = $request->input('inicio', $hoje->copy()->startOfMonth()->toDateString());
        $fim    = $request->input('fim',    $hoje->copy()->addMonth()->toDateString());

        $dados = DB::table('view_app_contasreceber')
            ->selectRaw('data_vencimento, SUM(saldo) AS total')
            ->whereBetween('data_vencimento', [$inicio, $fim])
            // pega somente títulos que ainda tenham saldo > 0 e estejam a vencer ou em atraso
            ->where('saldo', '>', 0)
            ->whereIn('situacao', ['A VENCER', 'EM ATRASO'])
            ->groupBy('data_vencimento')
            ->orderBy('data_vencimento')
            ->get();

        return view('relatorios.previsao-recebimentos', [
            'dados'  => $dados,
            'inicio' => $inicio,
            'fim'    => $fim,
        ]);
    }

    /**
     * Previsão de pagamentos (Contas a Pagar).
     * Aqui continua usando a tabela/model ContasPagar normal.
     * Depois, se você quiser, criamos uma view similar para o contas a pagar.
     */
    public function previsaoPagamentos(Request $request)
    {
        $hoje = Carbon::today();

        $inicio = $request->input('inicio', $hoje->copy()->startOfMonth()->toDateString());
        $fim    = $request->input('fim',    $hoje->copy()->addMonth()->toDateString());

        $dados = ContasPagar::selectRaw('data_vencimento, SUM(valor) as total')
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->where('status', 'ABERTO')
            ->groupBy('data_vencimento')
            ->orderBy('data_vencimento')
            ->get();

        return view('relatorios.previsao-pagamentos', [
            'dados'  => $dados,
            'inicio' => $inicio,
            'fim'    => $fim,
        ]);
    }

    /**
     * Inadimplência: títulos em atraso (vencidos, com saldo > 0),
     * agrupados por cliente, usando a view_app_contasreceber.
     */
    public function inadimplenciaReceber(Request $request)
    {
        // dias_desde_vencimento já vem calculado na view,
        // então podemos usar ele para faixas de atraso.
        $dados = DB::table('view_app_contasreceber')
            ->selectRaw("
                cliente_id,
                cliente_nome,
                SUM(saldo) AS total_em_aberto,
                SUM(CASE
                        WHEN dias_desde_vencimento BETWEEN 1 AND 30
                        THEN saldo ELSE 0 END) AS faixa_1_30,
                SUM(CASE
                        WHEN dias_desde_vencimento BETWEEN 31 AND 60
                        THEN saldo ELSE 0 END) AS faixa_31_60,
                SUM(CASE
                        WHEN dias_desde_vencimento BETWEEN 61 AND 90
                        THEN saldo ELSE 0 END) AS faixa_61_90,
                SUM(CASE
                        WHEN dias_desde_vencimento > 90
                        THEN saldo ELSE 0 END) AS faixa_acima_90
            ")
            ->where('situacao', 'EM ATRASO')
            ->where('saldo', '>', 0)
            ->groupBy('cliente_id', 'cliente_nome')
            ->orderByDesc('total_em_aberto')
            ->get();

        return view('relatorios.inadimplencia-recebimentos', [
            'dados'     => $dados,
            'data_base' => Carbon::today()->toDateString(),
        ]);
    }
    public function extratoCliente(Request $request)
    {
        // Lista de clientes para o filtro
        $clientes = DB::table('appcliente')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $clienteId = (int) $request->input('cliente_id', 0);
        $dataDe    = $request->input('data_de');
        $dataAte   = $request->input('data_ate');
        $status    = strtoupper(trim((string) $request->input('status', 'TODOS')));

        $movimentos    = collect();
        $saldoAnterior = 0.0;
        $totais = [
            'titulo' => 0.0,
            'pago'   => 0.0,
            'saldo'  => 0.0,
        ];

        if ($clienteId > 0) {
            // Query base usando a VIEW consolidada
            $q = DB::table('view_app_contasreceber as v')
                ->where('v.cliente_id', $clienteId);

            if (!empty($dataDe)) {
                $q->whereDate('v.data_vencimento', '>=', $dataDe);
            }
            if (!empty($dataAte)) {
                $q->whereDate('v.data_vencimento', '<=', $dataAte);
            }
            if ($status !== '' && $status !== 'TODOS') {
                $q->whereRaw('UPPER(v.status_titulo) = ?', [$status]);
            }

            $movimentos = $q
                ->orderBy('v.data_vencimento')
                ->orderBy('v.parcela')
                ->get();

            // Saldo anterior ao período (tudo que venceu antes de data_de)
            if (!empty($dataDe)) {
                $saldoAnterior = DB::table('view_app_contasreceber as v')
                    ->where('v.cliente_id', $clienteId)
                    ->whereDate('v.data_vencimento', '<', $dataDe)
                    ->sum('v.saldo');
            }

            // Totais do período
            $totais['titulo'] = (float) $movimentos->sum('valor_titulo');
            $totais['pago']   = (float) $movimentos->sum('valor_pago');
            $totais['saldo']  = (float) $movimentos->sum('saldo');
        }

        $filtros = [
            'cliente_id' => $clienteId ?: '',
            'data_de'    => $dataDe,
            'data_ate'   => $dataAte,
            'status'     => $status ?: 'TODOS',
        ];

        return view('relatorios.extrato_cliente', compact(
            'clientes',
            'movimentos',
            'saldoAnterior',
            'totais',
            'filtros'
        ));
    }
    public function extratoPedidosCliente(Request $request, int $cliente)
    {
        // Carrega dados do cliente
        $clienteObj = DB::table('appcliente')->where('id', $cliente)->first();
        if (!$clienteObj) {
            return redirect()->route('clientes.index')
                ->with('error', 'Cliente não encontrado.');
        }

        // Filtros
        $status   = strtoupper((string) $request->input('status', 'TODOS'));
        $dataDe   = $request->input('data_de');
        $dataAte  = $request->input('data_ate');

        // Base: pedidos de venda desse cliente
        $q = DB::table('apppedidovenda as p')
            ->leftJoin('apprevendedora as r', 'r.id', '=', 'p.revendedora_id')
            ->selectRaw('
                p.*,
                r.nome as revendedora_nome
            ')
            ->where('p.cliente_id', $cliente);

        // Filtro status (PENDENTE, ABERTO, ENTREGUE, CANCELADO, TODOS)
        if ($status !== '' && $status !== 'TODOS') {
            $q->whereRaw('UPPER(p.status) = ?', [$status]);
        }

        // Filtros de datas (data do pedido)
        if (!empty($dataDe)) {
            $q->whereDate('p.data_pedido', '>=', $dataDe);
        }
        if (!empty($dataAte)) {
            $q->whereDate('p.data_pedido', '<=', $dataAte);
        }

        // Resumo (clone da query)
        // Base: pedidos de venda desse cliente (para LISTAGEM)
        $q = DB::table('apppedidovenda as p')
            ->leftJoin('apprevendedora as r', 'r.id', '=', 'p.revendedora_id')
            ->selectRaw('
            p.*,
            r.nome as revendedora_nome
        ')
            ->where('p.cliente_id', $cliente);

        // Filtro status (PENDENTE, ABERTO, ENTREGUE, CANCELADO, TODOS)
        if ($status !== '' && $status !== 'TODOS') {
            $q->whereRaw('UPPER(p.status) = ?', [$status]);
        }

        // Filtros de datas (data do pedido)
        if (!empty($dataDe)) {
            $q->whereDate('p.data_pedido', '>=', $dataDe);
        }
        if (!empty($dataAte)) {
            $q->whereDate('p.data_pedido', '<=', $dataAte);
        }

        /**
         * 🔹 RESUMO: faz outra query só com agregações
         *    (sem p.* nem joins desnecessários)
         */
        $qResumo = DB::table('apppedidovenda as p')
            ->where('p.cliente_id', $cliente);

        if ($status !== '' && $status !== 'TODOS') {
            $qResumo->whereRaw('UPPER(p.status) = ?', [$status]);
        }
        if (!empty($dataDe)) {
            $qResumo->whereDate('p.data_pedido', '>=', $dataDe);
        }
        if (!empty($dataAte)) {
            $qResumo->whereDate('p.data_pedido', '<=', $dataAte);
        }

        $resumo = $qResumo
            ->selectRaw('
            COUNT(*)                         as qtd_pedidos,
            COALESCE(SUM(p.valor_total), 0)  as total_bruto,
            COALESCE(SUM(p.valor_liquido),0) as total_liquido
        ')
            ->first();

        // Lista paginada (mantém como já estava)
        $pedidos = $q->orderByDesc('p.data_pedido')
            ->orderByDesc('p.id')
            ->paginate(20)
            ->appends($request->query());


        // Lista paginada
        $pedidos = $q->orderByDesc('p.data_pedido')
            ->orderByDesc('p.id')
            ->paginate(20)
            ->appends($request->query());

        $filtros = [
            'status'   => $status,
            'data_de'  => $dataDe,
            'data_ate' => $dataAte,
        ];

        return view('relatorios.extrato_pedidos_cliente', [
            'cliente'  => $clienteObj,
            'pedidos'  => $pedidos,
            'resumo'   => $resumo,
            'filtros'  => $filtros,
        ]);
    }

    /**
     * Extrato de PRODUTOS comprados pelo cliente
     * (agrupa itens por produto, soma quantidade e valor)
     */
    public function extratoProdutosCliente(Request $request, int $cliente)
    {
        // Cliente
        $clienteObj = DB::table('appcliente')->where('id', $cliente)->first();
        if (!$clienteObj) {
            return redirect()->route('clientes.index')
                ->with('error', 'Cliente não encontrado.');
        }

        $dataDe  = $request->input('data_de');
        $dataAte = $request->input('data_ate');
        $status  = strtoupper((string) $request->input('status', 'ENTREGUE')); // default: só entregues

        // Base: pedidos + itens + produtos
        $base = DB::table('apppedidovenda as p')
            ->join('appitemvenda as i', 'i.pedido_id', '=', 'p.id')
            ->leftJoin('appproduto as pr', 'pr.id', '=', 'i.produto_id')
            ->where('p.cliente_id', $cliente);

        // Status do pedido (útil se quiser só entregues)
        if ($status !== '' && $status !== 'TODOS') {
            $base->whereRaw('UPPER(p.status) = ?', [$status]);
        }

        // Filtros de datas (data do pedido)
        if (!empty($dataDe)) {
            $base->whereDate('p.data_pedido', '>=', $dataDe);
        }
        if (!empty($dataAte)) {
            $base->whereDate('p.data_pedido', '<=', $dataAte);
        }

        // Agrupamento por produto
        $produtos = $base
            ->selectRaw('
                i.produto_id,
                pr.codfabnumero,
                pr.nome as produto_nome,
                SUM(i.quantidade)               as qtd_total,
                COALESCE(SUM(i.preco_total),0)  as valor_total,
                MIN(p.data_pedido)              as primeira_compra,
                MAX(p.data_pedido)              as ultima_compra
            ')
            ->groupBy('i.produto_id', 'pr.codfabnumero', 'pr.nome')
            ->orderBy('produto_nome')
            ->get();

        // Resumo geral
        $resumo = (object) [
            'qtd_itens'   => $produtos->count(),
            'qtd_total'   => (float) $produtos->sum('qtd_total'),
            'valor_total' => (float) $produtos->sum('valor_total'),
        ];

        $filtros = [
            'status'   => $status,
            'data_de'  => $dataDe,
            'data_ate' => $dataAte,
        ];

        return view('relatorios.extrato_produtos_cliente', [
            'cliente'  => $clienteObj,
            'produtos' => $produtos,
            'resumo'   => $resumo,
            'filtros'  => $filtros,
        ]);
    }
}
