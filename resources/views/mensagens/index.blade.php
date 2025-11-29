@extends('layouts.app') {{-- ajuste para o layout que você usa --}}

@section('content')
<div class="container">
    <h1 class="mb-4">Relatório de Mensagens</h1>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('mensagens.index') }}" class="mb-4 border rounded p-3">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Data de (envio)</label>
                <input type="date" name="data_de" value="{{ $filtros['data_de'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data até</label>
                <input type="date" name="data_ate" value="{{ $filtros['data_ate'] ?? '' }}" class="form-control">
            </div>

            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">-- todos --</option>
                    @foreach(['queued','sent','failed'] as $st)
                        <option value="{{ $st }}" @selected(($filtros['status'] ?? '') === $st)>
                            {{ strtoupper($st) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">-- todos --</option>
                    @foreach($tiposConhecidos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? '') === $tipo)>
                            {{ $tipo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Campanha</label>
                <select name="campanha_id" class="form-select">
                    <option value="">-- todas --</option>
                    @foreach($campanhas as $camp)
                        <option value="{{ $camp->id }}" @selected(($filtros['campanha_id'] ?? '') == $camp->id)>
                            {{ $camp->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">-- todos --</option>
                    @foreach($clientes as $cli)
                        <option value="{{ $cli->id }}" @selected(($filtros['cliente_id'] ?? '') == $cli->id)>
                            {{ $cli->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Pedido #</label>
                <input type="number" name="pedido_id" value="{{ $filtros['pedido_id'] ?? '' }}" class="form-control">
            </div>

            <div class="col-md-2">
                <label class="form-label">Canal</label>
                <select name="canal" class="form-select">
                    <option value="">-- todos --</option>
                    @foreach(['whatsapp','sms','email'] as $can)
                        <option value="{{ $can }}" @selected(($filtros['canal'] ?? '') === $can)>
                            {{ strtoupper($can) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Direção</label>
                <select name="direcao" class="form-select">
                    <option value="">-- ambas --</option>
                    @foreach(['outbound','inbound'] as $dir)
                        <option value="{{ $dir }}" @selected(($filtros['direcao'] ?? '') === $dir)>
                            {{ strtoupper($dir) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Busca (texto / cliente)</label>
                <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}" class="form-control" placeholder="parte do texto, nome do cliente...">
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('mensagens.index') }}" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

    {{-- TABELA --}}
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data envio</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Campanha</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Canal</th>
                    <th>Direção</th>
                    <th>Preview</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mensagens as $msg)
                    <tr>
                        <td>{{ $msg->id }}</td>
                        <td>
                            {{ optional($msg->sent_at ?? $msg->created_at)->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            {{ $msg->cliente?->nome ?? '-' }}
                        </td>
                        <td>
                            @if($msg->pedido_id)
                                #{{ $msg->pedido_id }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ $msg->campanha?->nome ?? '-' }}
                        </td>
                        <td>{{ $msg->tipo ?? '-' }}</td>
                        <td>
                            @php
                                $badgeClass = match($msg->status) {
                                    'sent'   => 'bg-success',
                                    'failed' => 'bg-danger',
                                    'queued' => 'bg-warning',
                                    default  => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ strtoupper($msg->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td>{{ strtoupper($msg->canal ?? '-') }}</td>
                        <td>{{ strtoupper($msg->direcao ?? '-') }}</td>
                        <td style="max-width: 250px;">
                            <span title="{{ $msg->conteudo }}">
                                {{ \Illuminate\Support\Str::limit($msg->conteudo, 80) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">
                            Nenhuma mensagem encontrada para os filtros informados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINAÇÃO --}}
    <div class="mt-3">
        {{ $mensagens->links() }}
    </div>
</div>
@endsection
