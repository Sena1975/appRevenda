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
}
