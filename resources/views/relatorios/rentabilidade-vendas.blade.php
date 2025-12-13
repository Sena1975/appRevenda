<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-700">
                Relatórios • Rentabilidade de Vendas
            </h2>
        </div>
    </x-slot>

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
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status" class="w-full border rounded-lg px-3 py-2">
                        @php
                            $opcoes = ['TODOS','PENDENTE','ABERTO','ENTREGUE','CANCELADO','PAGO','FINALIZADO'];
                        @endphp
                        @foreach($opcoes as $op)
                            <option value="{{ $op }}" @selected($filtros['status'] === $op)>{{ $op }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Se seu sistema usa outros status, me diga que eu ajusto.</p>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Base de custo</label>
                    <select name="base_custo" class="w-full border rounded-lg px-3 py-2">
                        <option value="produto" @selected($filtros['base_custo'] === 'produto')>
                            Produto (preco_compra)
                        </option>
                        <option value="estoque" @selected($filtros['base_custo'] === 'estoque')>
                            Estoque (ultimo_preco_compra)
                        </option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.rentabilidade-vendas') }}"
                       class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- RESUMO --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @php
                $fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
                $fmtPerc = fn($v) => number_format((float)$v, 2, ',', '.') . '%';
            @endphp

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Receita Líquida</div>
                <div class="text-lg font-semibold text-gray-800">{{ $fmt($totais['receita_liquida']) }}</div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Custo (estimado)</div>
                <div class="text-lg font-semibold text-gray-800">{{ $fmt($totais['custo_total']) }}</div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Lucro</div>
                <div class="text-lg font-semibold {{ $totais['lucro_total'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                    {{ $fmt($totais['lucro_total']) }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Margem (geral)</div>
                <div class="text-lg font-semibold text-gray-800">{{ $fmtPerc($totais['margem_total']) }}</div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Desconto (total)</div>
                <div class="text-lg font-semibold text-gray-800">{{ $fmt($totais['desconto_total']) }}</div>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Pedido</th>
                        <th class="text-left px-4 py-3">Data</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Revendedora</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Receita Líquida</th>
                        <th class="text-right px-4 py-3">Custo</th>
                        <th class="text-right px-4 py-3">Lucro</th>
                        <th class="text-right px-4 py-3">Margem</th>
                        <th class="text-right px-4 py-3">Ações</th>
                    </tr>               
                </thead>
                <tbody class="divide-y">
                    @forelse($vendas as $v)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800">#{{ $v->id }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ \Carbon\Carbon::parse($v->data_pedido)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $v->cliente_nome }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $v->revendedora_nome ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    {{ $v->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ $fmt($v->valor_liquido) }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ $fmt($v->custo_total) }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $v->lucro < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ $fmt($v->lucro) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800">
                                {{ $fmtPerc($v->margem_perc) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                Nenhuma venda encontrada para os filtros selecionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">
                {{ $vendas->links() }}
            </div>
        </div>

        <div class="text-[11px] text-gray-500">
            Observação: “Custo (estimado)” usa a base escolhida no filtro. Se você quiser custo real por compra/lote (médio/FIFO), a gente evolui na próxima etapa.
        </div>
    </div>
</x-app-layout>
