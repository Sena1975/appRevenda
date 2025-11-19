{{-- resources/views/contasreceber/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto p-6">
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

        {{-- Filtros --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded border mb-4">
            <div>
                <label class="block text-sm">Cliente</label>
                <input type="text" name="cliente" class="w-full border rounded" value="{{ $filtro['cliente'] ?? '' }}">
            </div>
            <div>
                <label class="block text-sm">Status</label>
                <select name="status" class="w-full border rounded">
                    @php $st = $filtro['status'] ?? 'ABERTO'; @endphp
                    <option value="TODOS" @selected($st === 'TODOS')>Todos</option>
                    <option value="ABERTO" @selected($st === 'ABERTO')>Em aberto</option>
                    <option value="PAGO" @selected($st === 'PAGO')>Baixado</option>
                    <option value="CANCELADO" @selected($st === 'CANCELADO')>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Vencimento de</label>
                <input type="date" name="dt_ini" class="w-full border rounded" value="{{ $filtro['dt_ini'] ?? '' }}">
            </div>
            <div>
                <label class="block text-sm">Até</label>
                <input type="date" name="dt_fim" class="w-full border rounded" value="{{ $filtro['dt_fim'] ?? '' }}">
            </div>
            <div class="md:col-span-4 flex items-end gap-2">
                <button class="px-3 py-2 bg-blue-600 text-white rounded">Filtrar</button>
                <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">Limpar</a>
            </div>
        </form>

        <div class="overflow-x-auto bg-white border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Cliente</th>
                        <th class="px-3 py-2 text-left">Forma / Plano</th>
                        <th class="px-3 py-2 text-left">Parcela</th>
                        <th class="px-3 py-2 text-left">Vencimento</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Valor (R$)</th>
                        <th class="px-3 py-2 text-center w-64">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($contas as $c)
                        @php
                            $status = strtoupper((string) $c->status);
                            $badge =
                                $status === 'PAGO'
                                    ? 'bg-green-100 text-green-700'
                                    : ($status === 'CANCELADO'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-yellow-100 text-yellow-700');
                        @endphp
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $c->id }}</td>

                            <td class="px-3 py-2">
                                {{ $c->cliente_nome ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $c->forma_pagamento ?? '-' }}<br>
                                @if (!empty($c->plano_pagamento))
                                    <span class="text-xs text-gray-500">{{ $c->plano_pagamento }}</span>
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                {{ $c->parcela }}/{{ $c->total_parcelas }}
                            </td>

                            <td class="px-3 py-2">
                                @if (!empty($c->vencimento ?? $c->data_vencimento))
                                    {{ \Carbon\Carbon::parse($c->vencimento ?? $c->data_vencimento)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ $status }}</span>
                            </td>

                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) $c->valor, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Detalhes sempre disponível --}}
                                    <a href="{{ route('contasreceber.show', $c->id) }}"
                                        class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
                                        Detalhes
                                    </a>

                                    {{-- Editar e Baixar: só se NÃO estiver PAGO nem CANCELADO --}}
                                    @if ($status !== 'PAGO' && $status !== 'CANCELADO')
                                        <a href="{{ route('contasreceber.edit', $c->id) }}"
                                            class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
                                            Editar
                                        </a>

                                        <a href="{{ route('contasreceber.baixa', $c->id) }}"
                                            class="px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">
                                            Baixar
                                        </a>
                                    @endif

                                    {{-- Recibo sempre disponível --}}
                                    <a href="{{ route('contasreceber.recibo', $c->id) }}"
                                        class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
                                        Recibo
                                    </a>

                                    {{-- Estornar: só se estiver PAGO --}}
                                    @if ($status === 'PAGO')
                                        <form action="{{ route('contasreceber.estornar', $c->id) }}" method="POST"
                                            onsubmit="return confirm('Estornar a baixa desta parcela?');">
                                            @csrf
                                            <button type="submit"
                                                class="px-2 py-1 text-xs rounded bg-orange-600 text-white hover:bg-orange-700">
                                                Estornar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>


                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">Nenhum registro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $contas->withQueryString()->links() }}
        </div>
    </div>
@endsection
