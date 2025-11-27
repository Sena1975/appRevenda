<?php

namespace App\Http\Controllers;

use App\Models\Indicacao;
use Illuminate\Http\Request;

class IndicacaoController extends Controller
{
    /**
     * Lista indicações (pendentes ou pagas) para controle de pagamento
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pendente');

        $query = Indicacao::with(['indicador', 'indicado', 'pedido']);

        if ($status === 'pendente' || $status === 'pago') {
            $query->where('status', $status);
        }

        // >>> AQUI: soma do prêmio das indicações do filtro atual
        // TROQUE 'valor_premio' PELO NOME REAL DO CAMPO NA SUA TABELA
        $totalPremio = (clone $query)->sum('valor_premio');

        $indicacoes = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends($request->query());

        $totais = [
            'pendentes' => Indicacao::where('status', 'pendente')->count(),
            'pagas'     => Indicacao::where('status', 'pago')->count(),
            'todas'     => Indicacao::count(),
        ];

        return view('indicacoes.index', compact(
            'indicacoes',
            'status',
            'totais',
            'totalPremio',   // <<< não esquece de enviar isso
        ));
    }

    /**
     * Confirma o pagamento da indicação (muda status para "pago")
     */
    public function pagar($id)
    {
        $indicacao = Indicacao::with(['indicador', 'indicado'])->findOrFail($id);

        if ($indicacao->status === 'pago') {
            return back()->with('info', 'Esta indicação já está marcada como paga.');
        }

        $indicacao->status = 'pago';

        // Se depois você criar uma coluna data_pagamento/pago_em, pode setar aqui:
        // $indicacao->data_pagamento = now();

        $indicacao->save();

        return back()->with(
            'success',
            'Pagamento da indicação confirmado com sucesso.'
        );
    }
}
