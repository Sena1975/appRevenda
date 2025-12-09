<?php

namespace App\Http\Controllers;

use App\Models\Indicacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndicacaoController extends Controller
{
    /**
     * Descobre o ID da empresa atual (usuário logado ou middleware EmpresaAtiva)
     */
    private function getEmpresaId(): int
    {
        $user    = Auth::user();
        $empresa = $user?->empresa;

        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        if (!$empresa) {
            abort(500, 'Empresa não definida para o usuário atual.');
        }

        return (int) $empresa->id;
    }

    /**
     * Lista indicações (pendentes ou pagas) para controle de pagamento
     */
    public function index(Request $request)
    {
        $empresaId = $this->getEmpresaId();
        $status    = $request->query('status', 'pendente');

        $query = Indicacao::daEmpresa($empresaId)
            ->with(['indicador', 'indicado', 'pedido']);

        if ($status === 'pendente' || $status === 'pago') {
            $query->where('status', $status);
        }

        // Soma do prêmio das indicações do filtro atual
        $totalPremio = (clone $query)->sum('valor_premio');

        $indicacoes = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends($request->query());

        // Totais também por empresa
        $totais = [
            'pendentes' => Indicacao::daEmpresa($empresaId)
                ->where('status', 'pendente')
                ->count(),
            'pagas' => Indicacao::daEmpresa($empresaId)
                ->where('status', 'pago')
                ->count(),
            'todas' => Indicacao::daEmpresa($empresaId)->count(),
        ];

        return view('indicacoes.index', compact(
            'indicacoes',
            'status',
            'totais',
            'totalPremio',
        ));
    }

    /**
     * Confirma o pagamento da indicação (muda status para "pago")
     */
    public function pagar($id)
    {
        $empresaId = $this->getEmpresaId();

        // Garante que só pega indicação da empresa atual
        $indicacao = Indicacao::daEmpresa($empresaId)
            ->with(['indicador', 'indicado'])
            ->findOrFail($id);

        if ($indicacao->status === 'pago') {
            return back()->with('info', 'Esta indicação já está marcada como paga.');
        }

        $indicacao->status = 'pago';
        // Se tiver campo data_pagamento, aproveita aqui:
        // $indicacao->data_pagamento = now();

        $indicacao->save();

        return back()->with(
            'success',
            'Pagamento da indicação confirmado com sucesso.'
        );
    }
}
