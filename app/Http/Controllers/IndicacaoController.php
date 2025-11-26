<?php

namespace App\Http\Controllers;

use App\Models\Indicacao;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IndicacaoController extends Controller
{
    /**
     * Lista de indicações com filtro por status
     * status = pendente (default), pago ou todos
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pendente');

        $query = Indicacao::with(['indicador', 'indicado', 'pedido'])
            ->orderBy('status')
            ->orderByDesc('id');

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $indicacoes = $query->paginate(20)->appends($request->only('status'));

        // Totais por status (pra você ter noção dos valores)
        $totais = Indicacao::selectRaw('status, COUNT(*) qtd, SUM(valor_premio) total_premio')
            ->groupBy('status')
            ->pluck('total_premio', 'status');

        return view('indicacoes.index', [
            'indicacoes' => $indicacoes,
            'filtroStatus' => $status,
            'totais' => $totais,
        ]);
    }

    /**
     * Confirma o pagamento do prêmio de indicação
     */
    public function pagar(Indicacao $indicacao)
    {
        if ($indicacao->status === 'pago') {
            return back()->with('info', 'Este prêmio já está marcado como PAGO.');
        }

        $indicacao->status = 'pago';
        $indicacao->data_pagamento = Carbon::now();
        $indicacao->save();

        return back()->with('success', 'Pagamento da indicação confirmado com sucesso!');
    }
}
