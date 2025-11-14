{{-- resources/views/vendas/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-700">Pedidos de Venda</h1>
            <a href="{{ route('vendas.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-medium shadow">
                Novo Pedido
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-3 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-3 p-3 rounded bg-blue-100 text-blue-800 text-sm">{{ session('info') }}</div>
        @endif

        {{-- FILTROS --}}
        <form method="GET" action="{{ route('vendas.index') }}" class="mb-4 bg-white border rounded p-3">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                {{-- Cliente --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cliente (nome)</label>
                    <input type="text" name="cliente" value="{{ request('cliente') }}"
                           class="w-full border rounded px-3 py-2 text-sm"
                           placeholder="Ex.: Maria Silva">
                </div>

                {{-- Data inicial --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Data inicial</label>
                    <input type="date" name="data_ini" value="{{ request('data_ini') }}"
                           class="w-full border rounded px-3 py-2 text-sm">
                </div>

                {{-- Data final --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Data final</label>
                    <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                           class="w-full border rounded px-3 py-2 text-sm">
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2 text-sm">
                        @php
                            $stSel = strtoupper(request('status', ''));
                            $opts = [
                                ''          => 'Todos',
                                'PENDENTE'  => 'Pendente',
                                'ABERTO'    => 'Aberto',
                                'ENTREGUE'  => 'Entregue',
                                'CANCELADO' => 'Cancelado',
                            ];
                        @endphp
                        @foreach ($opts as $value => $label)
                            <option value="{{ $value }}" @selected($stSel === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">
                    Filtrar
                </button>
                <a href="{{ route('vendas.index') }}" class="px-3 py-2 border rounded text-sm">
                    Limpar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto bg-white border rounded">
            <table class="min-w-full">
                <thead class="bg-gray-50 text-sm text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left w-16">#</th>
                        <th class="px-3 py-2 text-left">Cliente</th>
                        <th class="px-3 py-2 text-left">Revendedora</th>
                        <th class="px-3 py-2 text-left w-36">Data</th>
                        <th class="px-3 py-2 text-left w-32">Status</th>
                        <th class="px-3 py-2 text-right w-32">Total (R$)</th>
                        <th class="px-3 py-2 text-center w-48">Ações</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse ($pedidos as $p)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $p->id }}</td>
                            <td class="px-3 py-2">{{ $p->cliente->nome ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $p->revendedora->nome ?? '-' }}</td>
                            <td class="px-3 py-2">
                                {{ \Carbon\Carbon::parse($p->data_pedido)->format('d/m/Y') }}
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $status = strtoupper($p->status ?? 'PENDENTE');
                                    $badge = match ($status) {
                                        'ENTREGUE'  => 'bg-green-100 text-green-800',
                                        'CANCELADO' => 'bg-red-100 text-red-800',
                                        'ABERTO'    => 'bg-blue-100 text-blue-800',
                                        default     => 'bg-yellow-100 text-yellow-800',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ $status }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) ($p->valor_liquido ?? ($p->valor_total ?? 0)), 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    {{-- Visualizar --}}
                                    <a href="{{ route('vendas.show', $p->id) }}"
                                       class="text-blue-600 hover:text-blue-800"
                                       title="Visualizar">
                                        🔍
                                    </a>

                                    {{-- Editar --}}
                                    <a href="{{ route('vendas.edit', $p->id) }}"
                                       class="text-orange-500 hover:text-orange-700"
                                       title="Editar">
                                        ✏️
                                    </a>

                                    {{-- Confirmar entrega (só se status permitir) --}}
                                    @php $st = strtoupper($p->status ?? ''); @endphp
                                    @if (in_array($st, ['PENDENTE', 'ABERTO', 'RESERVADO']))
                                        <form method="POST"
                                              action="{{ route('vendas.confirmarEntrega', $p->id) }}"
                                              class="inline"
                                              onsubmit="return confirm('Confirmar entrega do pedido #{{ $p->id }}?');">
                                            @csrf
                                            <button type="submit"
                                                    class="text-green-600 hover:text-green-800"
                                                    title="Confirmar entrega">
                                                ✅
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500" title="Entrega já confirmada / cancelada">
                                            {{ $p->status }}
                                        </span>
                                    @endif

                                    {{-- Exportar CSV --}}
                                    <a href="{{ route('vendas.exportar', $p->id) }}"
                                       class="text-indigo-600 hover:text-indigo-800"
                                       title="Exportar CSV">
                                        📤
                                    </a>

                                    {{-- Cancelar (abre modal) --}}
                                    <button type="button"
                                            x-data
                                            @click="$dispatch('open-cancel-{{ $p->id }}')"
                                            class="text-red-600 hover:text-red-800"
                                            title="Cancelar pedido">
                                        🗑️
                                    </button>

                                    {{-- Modal Cancelar --}}
                                    <div x-data="{ open: false }"
                                         x-on:open-cancel-{{ $p->id }}.window="open=true"
                                         x-show="open"
                                         x-cloak
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                        <div class="bg-white rounded-lg shadow max-w-md w-full p-4">
                                            <h3 class="text-lg font-semibold mb-2">
                                                Cancelar pedido #{{ $p->id }}
                                            </h3>
                                            <p class="text-sm text-gray-600 mb-2">
                                                Informe o motivo do cancelamento:
                                            </p>
                                            <form method="POST" action="{{ route('vendas.cancelar', $p->id) }}">
                                                @csrf
                                                <textarea name="observacao"
                                                          class="w-full border rounded p-2 mb-3 text-sm"
                                                          rows="4"
                                                          required
                                                          minlength="5"
                                                          placeholder="Ex.: Cliente desistiu, pedido duplicado, etc."></textarea>

                                                <div class="flex justify-end gap-2">
                                                    <button type="button"
                                                            class="px-3 py-2 border rounded text-sm"
                                                            @click="open=false">
                                                        Voltar
                                                    </button>
                                                    <button type="submit"
                                                            class="px-3 py-2 bg-red-600 text-white rounded text-sm">
                                                        Confirmar cancelamento
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-6 text-center text-gray-500" colspan="7">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if (method_exists($pedidos, 'links'))
            <div class="mt-4">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>
@endsection
