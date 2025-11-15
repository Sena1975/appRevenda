<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Contas a Pagar
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6">
        {{-- Filtros --}}
        <div class="bg-white shadow rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('contaspagar.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">

                <div>
                    <label class="block text-gray-700 mb-1">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" @selected(($filtros['fornecedor_id'] ?? null) == $fornecedor->id)>
                                {{ $fornecedor->nomefantasia ?? ($fornecedor->razaosocial ?? $fornecedor->nome) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        <option value="ABERTO" @selected(($filtros['status'] ?? null) === 'ABERTO')>Aberto</option>
                        <option value="PAGO" @selected(($filtros['status'] ?? null) === 'PAGO')>Pago</option>
                        <option value="CANCELADO" @selected(($filtros['status'] ?? null) === 'CANCELADO')>Cancelado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Vencimento a partir de</label>
                    <input type="date" name="data_ini" value="{{ $filtros['data_ini'] ?? '' }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Vencimento até</label>
                    <input type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="md:col-span-4 flex justify-end gap-2 mt-2">
                    <a href="{{ route('contaspagar.index') }}"
                        class="px-3 py-1 bg-gray-200 text-gray-800 rounded text-xs">
                        Limpar
                    </a>
                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        {{-- Resumo --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 text-sm">
            <div class="bg-white shadow rounded-lg p-3">
                <div class="text-gray-500 text-xs">Total em Aberto</div>
                <div class="text-lg font-semibold text-red-600">
                    R$ {{ number_format($resumo['total_aberto'] ?? 0, 2, ',', '.') }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-3">
                <div class="text-gray-500 text-xs">Total Pago</div>
                <div class="text-lg font-semibold text-green-600">
                    R$ {{ number_format($resumo['total_pago'] ?? 0, 2, ',', '.') }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-3">
                <div class="text-gray-500 text-xs">Total Geral (todas as contas)</div>
                <div class="text-lg font-semibold text-gray-700">
                    R$ {{ number_format($resumo['total_geral'] ?? 0, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Vencimento</th>
                        <th class="px-3 py-2 text-left">Fornecedor</th>
                        <th class="px-3 py-2 text-left">Nota / Parcela</th>
                        <th class="px-3 py-2 text-right">Valor</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-left">Origem</th>
                        <th class="px-3 py-2 text-center">Ações</th>

                    </tr>
                </thead>
                <tbody>
                    @forelse ($contas as $conta)
                        @php
                            $estaVencida =
                                $conta->status === 'ABERTO' &&
                                $conta->data_vencimento &&
                                $conta->data_vencimento->isPast();
                        @endphp

                        <tr class="border-t {{ $estaVencida ? 'bg-red-50' : '' }}">
                            <td class="px-3 py-2 {{ $estaVencida ? 'text-red-700 font-semibold' : '' }}">
                                {{ optional($conta->data_vencimento)->format('d/m/Y') }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $conta->fornecedor->nomefantasia ?? ($conta->fornecedor->razaosocial ?? ($conta->fornecedor->nome ?? '-')) }}
                            </td>
                            <td class="px-3 py-2">
                                Nº {{ $conta->numero_nota ?? '-' }}
                                <span class="text-xs text-gray-500">
                                    ({{ $conta->parcela }}/{{ $conta->total_parcelas }})
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right {{ $estaVencida ? 'text-red-700 font-semibold' : '' }}">
                                R$ {{ number_format($conta->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($conta->status === 'ABERTO')
                                    <span
                                        class="px-2 py-1 text-xs rounded
                    {{ $estaVencida ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        ABERTO
                                    </span>
                                @elseif ($conta->status === 'PAGO')
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">PAGO</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">CANCELADO</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                Pedido #{{ $conta->compra->id ?? '-' }}
                            </td>

                            <td class="px-3 py-2 text-center">
                                @if ($conta->status === 'ABERTO')
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('contaspagar.edit', $conta->id) }}"
                                            class="px-2 py-1 text-xs bg-blue-600 text-white rounded">
                                            Editar
                                        </a>

                                        <a href="{{ route('contaspagar.formBaixa', $conta->id) }}"
                                            class="px-2 py-1 text-xs bg-green-600 text-white rounded">
                                            Baixar
                                        </a>
                                    </div>
                                @elseif ($conta->status === 'PAGO')
                                    {{-- Só ESTORNAR, sem editar nem baixar --}}
                                    <form method="POST" action="{{ route('contaspagar.estornar', $conta->id) }}"
                                        onsubmit="return confirm('Confirma o estorno deste pagamento?');">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs bg-red-600 text-white rounded">
                                            Estornar
                                        </button>
                                    </form>
                                @else
                                    {{-- CANCELADO ou outro status: sem ações --}}
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                        </tr>
                    @empty

                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                                Nenhuma conta encontrada para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
