<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Relatórios • Entrada de Mercadoria
        </h2>
    </x-slot>

    @php
        $fmt = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    @endphp

    <div class="max-w-7xl mx-auto p-4 space-y-4">

        {{-- FILTROS --}}
        <div class="bg-white shadow rounded-lg p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500">De</label>
                    <input type="date" name="de" value="{{ $filtros['de'] }}"
                        class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">Até</label>
                    <input type="date" name="ate" value="{{ $filtros['ate'] }}"
                        class="w-full border rounded-lg px-3 py-2" />
                </div>

                <div>
                    <label class="text-xs text-gray-500">Produto (cod ou descrição)</label>
                    <input type="text" name="produto" value="{{ $filtros['produto'] ?? '' }}"
                        placeholder="Ex: 12345 ou sabonete" class="w-full border rounded-lg px-3 py-2" />
                </div>

                <div class="flex gap-2 md:col-span-2">
                    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.entrada-mercadoria') }}"
                        class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- TOTAIS --}}
        <div class="overflow-x-auto">
            <div class="flex flex-nowrap gap-3">
                <div class="bg-white shadow rounded-lg p-3 w-52 flex-shrink-0">
                    <div class="text-[11px] text-gray-500">Qtd Total</div>
                    <div class="text-base font-semibold text-gray-800">{{ (int) $totais['qtd_total_geral'] }}</div>
                </div>
                <div class="bg-white shadow rounded-lg p-3 w-52 flex-shrink-0">
                    <div class="text-[11px] text-gray-500">Valor Total Compra</div>
                    <div class="text-base font-semibold text-gray-800">{{ $fmt($totais['valor_total_geral']) }}</div>
                </div>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Data</th>
                        <th class="text-left px-4 py-3">Pedido Compra</th>
                        <th class="text-left px-4 py-3">Nota(s)</th>
                        <th class="text-left px-4 py-3">Produto</th>
                        <th class="text-left px-4 py-3">Cod</th>
                        <th class="text-right px-4 py-3">Qtd</th>
                        <th class="text-right px-4 py-3">Preço Médio Unit</th>
                        <th class="text-right px-4 py-3">Valor Total</th>
                        <th class="text-left px-4 py-3">Fornecedor(es)</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($linhas as $l)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">
                                {{ \Carbon\Carbon::parse($l->data_entrada)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ !empty($l->numpedcompras) ? $l->numpedcompras : '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ !empty($l->numero_notas) ? $l->numero_notas : '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-800 font-semibold">
                                {{ $l->produto_nome }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $l->codfabnumero ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800">
                                {{ (int) $l->qtd_total }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800">
                                {{ $fmt($l->preco_medio_unit) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800 font-semibold">
                                {{ $fmt($l->valor_total_compra) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $l->fornecedores ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                Nenhuma entrada encontrada no período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">
                {{ $linhas->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
