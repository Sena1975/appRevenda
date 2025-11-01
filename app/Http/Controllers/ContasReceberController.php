<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContasReceber;
use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\Revendedora;
use Carbon\Carbon;

class ContasReceberController extends Controller
{
    /**
     * Lista contas a receber com filtros e totais
     */
    public function index(Request $request)
    {
        $hoje = Carbon::today()->toDateString();

        $query = ContasReceber::with(['cliente:id,nome', 'revendedora:id,nome', 'forma:id,nome'])
            ->when($request->filled('cliente_id'), fn ($q) =>
                $q->where('cliente_id', $request->cliente_id)
            )
            ->when($request->filled('revendedora_id'), fn ($q) =>
                $q->where('revendedora_id', $request->revendedora_id)
            )
            ->when($request->filled('forma_pagamento_id'), fn ($q) =>
                $q->where('forma_pagamento_id', $request->forma_pagamento_id)
            )
            ->when($request->filled('status'), function ($q) use ($request, $hoje) {
                $status = strtoupper($request->status);
                if ($status === 'VENCIDO') {
                    $q->where('status', 'ABERTO')->whereDate('data_vencimento', '<', $hoje);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->filled('vencimento_de'), fn ($q) =>
                $q->whereDate('data_vencimento', '>=', $request->vencimento_de)
            )
            ->when($request->filled('vencimento_ate'), fn ($q) =>
                $q->whereDate('data_vencimento', '<=', $request->vencimento_ate)
            )
            ->when($request->filled('pedido_id'), fn ($q) =>
                $q->where('pedido_id', $request->pedido_id)
            );

        // Clona a query para calcular totais sem perder os filtros
        $base = (clone $query)->get();

        $total_geral   = $base->sum('valor');
        $total_aberto  = $base->filter(fn($c) => $c->status === 'ABERTO')->sum('valor');
        $total_pago    = $base->filter(fn($c) => $c->status === 'PAGO')->sum('valor');
        $total_vencido = $base->filter(function ($c) use ($hoje) {
            return $c->status === 'ABERTO' && $c->data_vencimento < $hoje;
        })->sum('valor');

        $contas = $query->orderBy('data_vencimento')->paginate(15)->withQueryString();

        // Combos
        $clientes = Cliente::orderBy('nome')->get(['id','nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id','nome']);
        $formas = FormaPagamento::orderBy('nome')->get(['id','nome']);

        return view('contasreceber.index', compact(
            'contas','clientes','revendedoras','formas',
            'total_geral','total_aberto','total_pago','total_vencido','hoje'
        ));
    }
}
