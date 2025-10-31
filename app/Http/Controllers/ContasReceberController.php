<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContasReceber;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\PedidoVenda;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContasReceberController extends Controller
{
    /**
     * Lista todos os títulos (abertos, pagos, cancelados)
     */
// app/Http/Controllers/ContasReceberController.php
public function index(Request $request)
{
    $q       = trim((string)$request->get('q', ''));                 // busca por cliente ou pedido
    $status  = $request->get('status', '');                          // ABERTO | BAIXADO | CANCELADO
    $ini     = $request->get('ini', '');                              // data inicial (vencimento)
    $fim     = $request->get('fim', '');                              // data final (vencimento)
    $formaId = $request->get('forma_pagamento_id', '');               // opcional
    $pedido  = $request->get('pedido_id', '');                        // opcional

    $qry = \App\Models\ContasReceber::with(['cliente:id,nome','revendedora:id,nome'])
        ->orderBy('data_vencimento', 'asc');

    if ($status !== '') {
        $qry->where('status', $status);
    }

    if ($formaId !== '') {
        $qry->where('forma_pagamento_id', $formaId);
    }

    if ($pedido !== '') {
        $qry->where('pedido_id', (int)$pedido);
    }

    // Período de vencimento
    if ($ini) $qry->whereDate('data_vencimento', '>=', $ini);
    if ($fim) $qry->whereDate('data_vencimento', '<=', $fim);

    // Busca texto (cliente ou revendedora ou observação)
    if ($q !== '') {
        $qry->where(function ($w) use ($q) {
            $w->whereHas('cliente', function ($c) use ($q) {
                $c->where('nome', 'like', "%{$q}%");
            })->orWhereHas('revendedora', function ($r) use ($q) {
                $r->where('nome', 'like', "%{$q}%");
            })->orWhere('observacao', 'like', "%{$q}%")
             ->orWhere('pedido_id', intval($q) ?: 0);
        });
    }

    // Clonar para totalizadores SEM quebrar a paginação
    $base = (clone $qry);

    $totalAberto    = (clone $base)->where('status', 'ABERTO')->sum('valor');
    $totalBaixado   = (clone $base)->where('status', 'BAIXADO')->sum('valor');
    $totalCancelado = (clone $base)->where('status', 'CANCELADO')->sum('valor');
    $totalGeral     = (clone $base)->sum('valor');

    $parcelas = $qry->paginate(20)->appends($request->query());

    // Preencher selects auxiliares (formas de pagamento)
    $formas = \App\Models\FormaPagamento::orderBy('nome')->get(['id','nome']);

    return view('contasreceber.index', compact(
        'parcelas', 'formas',
        'q','status','ini','fim','formaId','pedido',
        'totalAberto','totalBaixado','totalCancelado','totalGeral'
    ));
}

    /**
     * Exibe detalhes de uma conta
     */
    public function show($id)
    {
        $conta = DB::table('appcontasreceber as cr')
            ->join('appcliente as c', 'c.id', '=', 'cr.cliente_id')
            ->join('apprevendedora as r', 'r.id', '=', 'cr.revendedora_id')
            ->join('apppedidovenda as p', 'p.id', '=', 'cr.pedido_id')
            ->select(
                'cr.*',
                'c.nome as cliente_nome',
                'r.nome as revendedora_nome',
                'p.valor_liquido as pedido_valor',
                'p.data_pedido'
            )
            ->where('cr.id', $id)
            ->first();

        if (!$conta) {
            return redirect()->route('contas.index')->with('error', 'Conta não encontrada.');
        }

        return view('financeiro.show', compact('conta'));
    }
}
