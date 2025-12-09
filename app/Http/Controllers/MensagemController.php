<?php

namespace App\Http\Controllers;

use App\Models\Mensagem;
use App\Models\Cliente;
use App\Models\Campanha;
use App\Models\PedidoVenda;
use Illuminate\Http\Request;

class MensagemController extends Controller
{
    public function index(Request $request)
    {
        // Validação simples de filtros
        $filtros = $request->validate([
            'data_de'      => 'nullable|date',
            'data_ate'     => 'nullable|date',
            'status'       => 'nullable|string|max:50',
            'tipo'         => 'nullable|string|max:100',
            'canal'        => 'nullable|string|max:50',
            'direcao'      => 'nullable|string|max:50',
            'cliente_id'   => 'nullable|integer',
            'campanha_id'  => 'nullable|integer',
            'pedido_id'    => 'nullable|integer',
            'busca'        => 'nullable|string|max:255',
        ]);

        $query = Mensagem::with(['cliente:id,nome', 'campanha:id,nome', 'pedido:id,cliente_id'])
            ->orderByDesc('sent_at') // mais recente primeiro
            ->orderByDesc('id');

        $query
            ->doPeriodo($filtros['data_de']   ?? null, $filtros['data_ate'] ?? null)
            ->status($filtros['status']       ?? null)
            ->tipo($filtros['tipo']           ?? null)
            ->canal($filtros['canal']         ?? null)
            ->direcao($filtros['direcao']     ?? null)
            ->porCliente($filtros['cliente_id']  ?? null)
            ->porCampanha($filtros['campanha_id'] ?? null)
            ->porPedido($filtros['pedido_id']  ?? null)
            ->buscaLivre($filtros['busca']    ?? null);

        $mensagens = $query->paginate(20)->appends($request->query());

        // Para combos de filtro
        $clientes  = Cliente::orderBy('nome')->get(['id', 'nome']);
        $campanhas = Campanha::orderBy('nome')->get(['id', 'nome']);

        // Lista de tipos conhecidos (opcional; você pode preencher manualmente)
        $tiposConhecidos = Mensagem::select('tipo')
            ->whereNotNull('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo');

        return view('mensagens.index', [
            'mensagens'      => $mensagens,
            'clientes'       => $clientes,
            'campanhas'      => $campanhas,
            'tiposConhecidos' => $tiposConhecidos,
            'filtros'        => $filtros,
        ]);
    }

    public function show(Mensagem $mensagem)
    {
        // carrega relações para usar na view
        $mensagem->load(['cliente', 'pedido', 'campanha']);

        return view('mensagens.show', compact('mensagem'));
    }
}
