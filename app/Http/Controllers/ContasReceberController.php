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
    public function index()
    {
        $contas = DB::table('appcontasreceber as cr')
            ->join('appcliente as c', 'c.id', '=', 'cr.cliente_id')
            ->join('apprevendedora as r', 'r.id', '=', 'cr.revendedora_id')
            ->select(
                'cr.*',
                'c.nome as cliente_nome',
                'r.nome as revendedora_nome'
            )
            ->orderBy('cr.data_vencimento', 'asc')
            ->get();

        return view('financeiro.index', compact('contas'));
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
