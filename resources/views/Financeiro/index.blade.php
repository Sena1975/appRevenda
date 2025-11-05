@extends('layouts.app')

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Contas a Receber</h1>

        @if (session('success'))
            <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
        @endif

        {{-- FILTROS --}}
        <form method="GET" class="mb-4 overflow-x-auto">
            <div class="min-w-[720px] flex items-end gap-3">
                <div class="w-80">
                    <label class="block text-sm font-medium">Cliente</label>
                    <select name="cliente_id" class="w-full border rounded">
                        <option value="">Todos</option>
                        @foreach ($clientes as $cli)
                            <option value="{{ $cli->id }}" @selected(request('cliente_id') == $cli->id)>{{ $cli->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Venc. de</label>
                    <input type="date" name="data_ini" value="{{ request('data_ini') }}" class="border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium">até</label>
                    <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="border rounded">
                </div>
                <div class="w-48">
                    <label class="block text-sm font-medium">Status</label>
                    <select name="status" class="w-full border rounded">
                        @php $st = request('status','TODOS'); @endphp
                        <option value="TODOS" @selected($st === 'TODOS')>Todos</option>
                        <option value="PENDENTES" @selected($st === 'PENDENTES')>Pendentes</option>
                        <option value="ABERTO" @selected($st === 'ABERTO')>Aberto</option>
                        <option value="PAGO" @selected($st === 'PAGO')>Pago</option>
                        <option value="CANCELADO" @selected($st === 'CANCELADO')>Cancelado</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="px-3 py-2 bg-blue-600 text-white rounded">Filtrar</button>
                    <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">Limpar</a>
                </div>
            </div>
        </form>

        {{-- GRID --}}
        <div class="overflow-x-auto">
            <table class="min-w-[960px] w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Cliente</th>
                        <th class="px-3 py-2 text-left">Parcela</th>
                        <th class="px-3 py-2 text-left">Forma</th>
                        <th class="px-3 py-2 text-left">Vencimento</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Valor (R$)</th>
                        <th class="px-3 py-2 text-center w-64">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contas as $c)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $c->id }}</td>
                            <td class="px-3 py-2">{{ $c->cliente->nome ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $c->parcela }}/{{ $c->total_parcelas }}</td>
                            <td class="px-3 py-2">{{ $c->forma->nome ?? '-' }}</td>
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">
                                @php
                                    $st = strtoupper($c->status);
                                    $badge = match ($st) {
                                        'PAGO' => 'bg-green-100 text-green-700',
                                        'CANCELADO' => 'bg-red-100 text-red-700',
                                        default => 'bg-yellow-100 text-yellow-700',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ $st }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $c->valor, 2, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('contasreceber.show', $c->id) }}"
                                        class="px-2 py-1 text-xs border rounded hover:bg-gray-50">Detalhes</a>
                                    @if ($c->status !== 'PAGO')
                                        <a href="{{ route('contasreceber.baixa', $c->id) }}"
                                            class="px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">Baixar</a>
                                    @else
                                        <a href="{{ route('contasreceber.recibo', $c->id) }}"
                                            class="px-2 py-1 text-xs border rounded hover:bg-gray-50">Recibo</a>
                                    @endif
                                    <a href="{{ route('contasreceber.edit', $c->id) }}"
                                        class="px-2 py-1 text-xs border rounded hover:bg-gray-50">Editar</a>
                                    <form action="{{ route('contasreceber.estornar', $c->id) }}" method="POST"
                                        onsubmit="return confirm('Estornar baixa da parcela #{{ $c->id }}?');"
                                        class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="px-2 py-1 text-xs rounded border border-orange-500 text-orange-600 hover:bg-orange-50">
                                            Estornar
                                        </button>
                                    </form>
                                </div>



                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-gray-500">Nenhum título encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($contas, 'links'))
            <div class="mt-4">{{ $contas->links() }}</div>
        @endif
    </div>
@endsection
