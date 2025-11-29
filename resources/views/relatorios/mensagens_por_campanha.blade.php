{{-- resources/views/relatorios/mensagens_por_campanha.blade.php --}}
@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app') {{-- ajuste para o layout que você usa --}}

@section('content')
<div class="container">
    <h1 class="mb-4">Relatório de Mensagens por Campanha</h1>

    {{-- FILTROS --}}
    <div class="card mb-4">
        <div class="card-header">
            Filtros
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('relatorios.mensagens.por_campanha') }}" class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Data inicial (sent_at)</label>
                    <input type="date"
                           name="data_de"
                           value="{{ $filtros['data_de'] ?? '' }}"
                           class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Data final (sent_at)</label>
                    <input type="date"
                           name="data_ate"
                           value="{{ $filtros['data_ate'] ?? '' }}"
                           class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Campanha</label>
                    <select name="campanha_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($campanhas as $campanha)
                            <option value="{{ $campanha->id }}"
                                @selected(($filtros['campanha_id'] ?? null) == $campanha->id)>
                                {{ $campanha->nome }} (ID: {{ $campanha->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status da mensagem</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['queued','sent','failed'] as $st)
                            <option value="{{ $st }}"
                                @selected(($filtros['status'] ?? null) === $st)>
                                {{ strtoupper($st) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipo da mensagem</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tiposConhecidos as $tipo)
                            <option value="{{ $tipo }}"
                                @selected(($filtros['tipo'] ?? null) === $tipo)>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.mensagens.por_campanha') }}" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- RESUMO POR CAMPANHA --}}
    <div class="card mb-4">
        <div class="card-header">
            Resumo por campanha (após filtros)
        </div>
        <div class="card-body p-0">
            @if($resumoPorCampanha->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhuma mensagem encontrada para os filtros selecionados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Campanha</th>
                                <th>ID</th>
                                <th>Total</th>
                                <th>Enviadas (sent)</th>
                                <th>Falhas (failed)</th>
                                <th>Em fila (queued)</th>
                                <th>% Falha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumoPorCampanha as $r)
                                @php
                                    $totalBase = ($r->total_enviadas ?? 0) + ($r->total_falhas ?? 0);
                                    $taxaFalha = $totalBase > 0
                                        ? round(($r->total_falhas ?? 0) * 100 / $totalBase, 1)
                                        : 0;
                                    $camp = $campanhas->firstWhere('id', $r->campanha_id);
                                @endphp
                                <tr>
                                    <td>{{ $camp->nome ?? 'Campanha #'.$r->campanha_id }}</td>
                                    <td>{{ $r->campanha_id }}</td>
                                    <td>{{ $r->total }}</td>
                                    <td>{{ $r->total_enviadas }}</td>
                                    <td>{{ $r->total_falhas }}</td>
                                    <td>{{ $r->total_queued }}</td>
                                    <td>{{ $taxaFalha }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- RESUMO GLOBAL POR TIPO (OPCIONAL) --}}
    @isset($resumoPorTipo)
    <div class="card mb-4">
        <div class="card-header">
            Resumo por tipo de mensagem (após filtros)
        </div>
        <div class="card-body p-0">
            @if($resumoPorTipo->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhum dado para exibir.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>Enviadas</th>
                                <th>Falhas</th>
                                <th>Em fila</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumoPorTipo as $rt)
                                <tr>
                                    <td>{{ $rt->tipo ?? '(sem tipo)' }}</td>
                                    <td>{{ $rt->total }}</td>
                                    <td>{{ $rt->total_enviadas }}</td>
                                    <td>{{ $rt->total_falhas }}</td>
                                    <td>{{ $rt->total_queued }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endisset

    {{-- LISTAGEM DETALHADA --}}
    <div class="card">
        <div class="card-header">
            Mensagens (detalhe)
        </div>
        <div class="card-body p-0">
            @if($mensagens->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhuma mensagem encontrada.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sent at</th>
                                <th>Status</th>
                                <th>Tipo</th>
                                <th>Campanha</th>
                                <th>Cliente</th>
                                <th>Pedido</th>
                                <th>Conteúdo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mensagens as $msg)
                                <tr>
                                    <td>{{ $msg->id }}</td>
                                    <td>
                                        @if($msg->sent_at)
                                            {{ $msg->sent_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($msg->status) {
                                                'sent'   => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'queued' => 'bg-secondary',
                                                default  => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $msg->status ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $msg->tipo ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($msg->campanha)
                                            {{ $msg->campanha->nome }} ({{ $msg->campanha_id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($msg->cliente)
                                            {{ $msg->cliente->nome }} (ID {{ $msg->cliente_id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($msg->pedido)
                                            #{{ $msg->pedido->id }}
                                        @elseif($msg->pedido_id)
                                            #{{ $msg->pedido_id }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span title="{{ $msg->conteudo }}">
                                            {{ Str::limit($msg->conteudo, 80) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $mensagens->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
