{{-- resources/views/indicacoes/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Indicações de Clientes / Prêmios
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Mensagens de sucesso/erro --}}
        @if (session('success'))
            <div class="p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="p-3 rounded bg-blue-100 text-blue-700 text-sm">
                {{ session('info') }}
            </div>
        @endif

        {{-- Filtros simples de status --}}
        <div class="bg-white shadow rounded-lg p-4 flex items-center justify-between">
            <div class="space-x-2">
                <a href="{{ route('indicacoes.index', ['status' => 'pendente']) }}"
                   class="px-3 py-1 rounded text-sm
                        {{ ($filtroStatus ?? 'pendente') === 'pendente'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700' }}">
                    Pendentes
                </a>
                <a href="{{ route('indicacoes.index', ['status' => 'pago']) }}"
                   class="px-3 py-1 rounded text-sm
                        {{ ($filtroStatus ?? 'pendente') === 'pago'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700' }}">
                    Pagas
                </a>
                <a href="{{ route('indicacoes.index', ['status' => 'todos']) }}"
                   class="px-3 py-1 rounded text-sm
                        {{ ($filtroStatus ?? 'pendente') === 'todos'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700' }}">
                    Todas
                </a>
            </div>

            {{-- Resumo rápido dos valores --}}
            <div class="text-right text-sm text-gray-600 space-y-1">
                <div>
                    Pendentes:
                    <span class="font-semibold">
                        R$ {{ number_format($totais['pendente'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div>
                    Pagas:
                    <span class="font-semibold">
                        R$ {{ number_format($totais['pago'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tabela de indicações --}}
        <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="py-2 pr-3">#</th>
                        <th class="py-2 pr-3">Data</th>
                        <th class="py-2 pr-3">Indicador</th>
                        <th class="py-2 pr-3">Indicado</th>
                        <th class="py-2 pr-3">Pedido</th>
                        <th class="py-2 pr-3 text-right">Valor Pedido</th>
                        <th class="py-2 pr-3 text-right">Prêmio (R$)</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Data Pagamento</th>
                        <th class="py-2 pr-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indicacoes as $ind)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-1 pr-3">
                                {{ $ind->id }}
                            </td>
                            <td class="py-1 pr-3">
                                {{ optional($ind->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-1 pr-3">
                                {{ $ind->indicador->nome ?? ('ID-' . $ind->indicador_id) }}
                            </td>
                            <td class="py-1 pr-3">
                                {{ $ind->indicado->nome ?? ('ID-' . $ind->indicado_id) }}
                            </td>
                            <td class="py-1 pr-3">
                                @if ($ind->pedido)
                                    Pedido #{{ $ind->pedido->id }}
                                @else
                                    ID-{{ $ind->pedido_id }}
                                @endif
                            </td>
                            <td class="py-1 pr-3 text-right">
                                R$ {{ number_format($ind->valor_pedido, 2, ',', '.') }}
                            </td>
                            <td class="py-1 pr-3 text-right font-semibold text-green-700">
                                R$ {{ number_format($ind->valor_premio, 2, ',', '.') }}
                            </td>
                            <td class="py-1 pr-3">
                                @if ($ind->status === 'pendente')
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                        Pendente
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                                        Pago
                                    </span>
                                @endif
                            </td>
                            <td class="py-1 pr-3">
                                {{ $ind->data_pagamento
                                    ? \Carbon\Carbon::parse($ind->data_pagamento)->format('d/m/Y H:i')
                                    : '-' }}
                            </td>
                            <td class="py-1 pr-3 text-center">
                                @if ($ind->status === 'pendente')
                                    <form action="{{ route('indicacoes.pagar', $ind->id) }}" method="POST"
                                          onsubmit="return confirm('Confirmar pagamento desse prêmio?');">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">
                                            Confirmar Pagamento
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-4 text-center text-gray-500">
                                Nenhuma indicação encontrada para o filtro selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $indicacoes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
