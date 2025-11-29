<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Mensagem;
use App\Models\Campanha;
use App\Models\Indicacao;

class RelatorioMensagensController extends Controller
{
    /**
     * Relatório: Mensagens por campanha
     */
// app/Http/Controllers/RelatorioMensagensController.php

public function porCampanha(Request $request)
{
    $dataDe     = $request->query('data_de');
    $dataAte    = $request->query('data_ate');
    $campanhaId = $request->query('campanha_id');
    $status     = $request->query('status');
    $tipo       = $request->query('tipo');

    // 1) QUERY BASE COM FILTROS
    $query = Mensagem::query()
        ->with(['campanha:id,nome', 'cliente:id,nome', 'pedido:id'])
        ->whereNotNull('campanha_id')
        ->where('canal', 'whatsapp')
        ->where('direcao', 'outbound');

    // Filtro por período (sent_at)
    if ($dataDe) {
        $query->whereDate('sent_at', '>=', $dataDe);
    }
    if ($dataAte) {
        $query->whereDate('sent_at', '<=', $dataAte);
    }

    if ($campanhaId) {
        $query->where('campanha_id', $campanhaId);
    }

    if ($status) {
        $query->where('status', $status);
    }

    if ($tipo) {
        $query->where('tipo', $tipo);
    }

    // 2) RESUMO POR CAMPANHA (APÓS FILTROS)
    $resumoPorCampanha = (clone $query)
        ->select(
            'campanha_id',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) as total_enviadas"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_falhas"),
            DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as total_queued")
        )
        ->groupBy('campanha_id')
        ->get();

    // 3) RESUMO GLOBAL POR TIPO (TAMBÉM COM MESMOS FILTROS)
    //    Aqui entra exatamente o "resumo global com filtros tipo, campanha_id e período"
    $resumoPorTipo = (clone $query)
        ->select(
            'tipo',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) as total_enviadas"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_falhas"),
            DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as total_queued")
        )
        ->groupBy('tipo')
        ->orderBy('tipo')
        ->get();

    // 4) LISTAGEM DETALHADA (PAGINADA)
    $mensagens = $query
        ->orderByDesc('sent_at')
        ->orderByDesc('id')
        ->paginate(20)
        ->appends($request->query());

    // Dados auxiliares para filtros
    $campanhas = Campanha::orderBy('nome')->get(['id','nome']);

    $tiposConhecidos = Mensagem::whereNotNull('tipo')
        ->distinct()
        ->pluck('tipo')
        ->sort()
        ->values();

    return view('relatorios.mensagens_por_campanha', [
        'mensagens'         => $mensagens,
        'resumoPorCampanha' => $resumoPorCampanha,
        'resumoPorTipo'     => $resumoPorTipo, // <- usado na view
        'campanhas'         => $campanhas,
        'tiposConhecidos'   => $tiposConhecidos,
        'filtros'           => [
            'data_de'     => $dataDe,
            'data_ate'    => $dataAte,
            'campanha_id' => $campanhaId,
            'status'      => $status,
            'tipo'        => $tipo,
        ],
    ]);
}

    
    public function porCliente(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        $dataDe    = $request->query('data_de');
        $dataAte   = $request->query('data_ate');
        $status    = $request->query('status');
        $tipo      = $request->query('tipo');

        $query = Mensagem::with(['cliente:id,nome', 'pedido:id,cliente_id', 'campanha:id,nome'])
            ->where('canal', 'whatsapp')
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        if ($dataDe) {
            $query->whereDate('sent_at', '>=', $dataDe);
        }

        if ($dataAte) {
            $query->whereDate('sent_at', '<=', $dataAte);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        // Se quiser limitar só a outbound:
        // $query->where('direcao', 'outbound');

        $mensagens = $query->paginate(30)->appends($request->query());

        $clientes = \App\Models\Cliente::orderBy('nome')->get(['id', 'nome']);

        $tiposConhecidos = Mensagem::whereNotNull('tipo')
            ->distinct()
            ->pluck('tipo')
            ->sort()
            ->values();

        return view('relatorios.mensagens_por_cliente', [
            'mensagens'       => $mensagens,
            'clientes'        => $clientes,
            'tiposConhecidos' => $tiposConhecidos,
            'filtros'         => [
                'cliente_id' => $clienteId,
                'data_de'    => $dataDe,
                'data_ate'   => $dataAte,
                'status'     => $status,
                'tipo'       => $tipo,
            ],
        ]);
    }

    public function campanhasIndicacao(Request $request)
    {
        $campanhaId = $request->query('campanha_id');
        $dataDe     = $request->query('data_de');
        $dataAte    = $request->query('data_ate');
        $status     = $request->query('status'); // pendente, pago, cancelado

        $query = Indicacao::query()
            ->with([
                'indicado:id,nome',
                'indicador:id,nome',
                'campanha:id,nome',
                'pedido:id,cliente_id,valor_total,valor_liquido,data_pedido,status',
            ]);

        if ($campanhaId) {
            $query->where('campanha_id', $campanhaId);
        }

        if ($dataDe) {
            $query->whereDate('created_at', '>=', $dataDe);
        }

        if ($dataAte) {
            $query->whereDate('created_at', '<=', $dataAte);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // 🔹 Listagem detalhada (cada indicação)
        $indicacoes = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        // 🔹 Resumo agregado por campanha
        $resumoPorCampanha = Indicacao::query()
            ->select(
                'campanha_id',
                DB::raw('COUNT(*) as total_indicacoes'),
                DB::raw("SUM(CASE WHEN status = 'pago'      THEN 1 ELSE 0 END) as total_pagas"),
                DB::raw("SUM(CASE WHEN status = 'pendente'  THEN 1 ELSE 0 END) as total_pendentes"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as total_canceladas"),
                DB::raw('SUM(valor_pedido) as soma_valor_pedido'),
                DB::raw('SUM(valor_premio) as soma_valor_premio')
            )
            ->when($campanhaId, fn($q) => $q->where('campanha_id', $campanhaId))
            ->when($dataDe,    fn($q) => $q->whereDate('created_at', '>=', $dataDe))
            ->when($dataAte,   fn($q) => $q->whereDate('created_at', '<=', $dataAte))
            ->groupBy('campanha_id')
            ->get();

        $campanhas = Campanha::orderBy('nome')->get(['id', 'nome']);

        return view('relatorios.campanhas_indicacao', [
            'indicacoes'       => $indicacoes,
            'resumoPorCampanha' => $resumoPorCampanha,
            'campanhas'        => $campanhas,
            'filtros'          => [
                'campanha_id' => $campanhaId,
                'data_de'     => $dataDe,
                'data_ate'    => $dataAte,
                'status'      => $status,
            ],
        ]);
    }
}
