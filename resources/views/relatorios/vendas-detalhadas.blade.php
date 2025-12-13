<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Relatórios • Vendas Detalhadas (por Período)
        </h2>
    </x-slot>

    @php
        $fmt = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $fmtPerc = fn($v) => number_format((float) $v, 2, ',', '.') . '%';
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
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status" class="w-full border rounded-lg px-3 py-2">
                        @php $opcoes = ['TODOS','PENDENTE','ABERTO','ENTREGUE','CANCELADO','PAGO','FINALIZADO']; @endphp
                        @foreach ($opcoes as $op)
                            <option value="{{ $op }}" @selected($filtros['status'] === $op)>{{ $op }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.vendas-detalhadas') }}"
                        class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

{{-- TOTAIS DO PERÍODO (compacto, sempre 6 na mesma linha) --}}
<div class="overflow-x-auto">
    <div class="flex flex-nowrap gap-3">
        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Pedidos</div>
            <div class="text-base font-semibold text-gray-800">{{ $totais['qtd_pedidos'] }}</div>
        </div>

        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Receita Líquida</div>
            <div class="text-base font-semibold text-gray-800">{{ $fmt($totais['receita_liquida']) }}</div>
        </div>

        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Custo</div>
            <div class="text-base font-semibold text-gray-800">{{ $fmt($totais['custo_total']) }}</div>
        </div>

        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Lucro</div>
            <div class="text-base font-semibold {{ $totais['lucro_total'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ $fmt($totais['lucro_total']) }}
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Margem</div>
            <div class="text-base font-semibold text-gray-800">{{ $fmtPerc($totais['margem_total']) }}</div>
        </div>

        <div class="bg-white shadow rounded-lg p-3 w-44 flex-shrink-0">
            <div class="text-[11px] text-gray-500">Pontos</div>
            <div class="text-xs text-gray-700">Pts: <span class="font-semibold">{{ $totais['pontos'] }}</span></div>
            <div class="text-xs text-gray-700">Pts Total: <span class="font-semibold">{{ $totais['pontos_total'] }}</span></div>
        </div>
    </div>
</div>


        {{-- LISTA DE PEDIDOS (cards) --}}
        <div class="space-y-3">
            @forelse($pedidos as $p)
                @php
                    $calc = $p->calc ?? [];
                @endphp

                <div x-data="{ open: false }" class="bg-white shadow rounded-lg">
                    {{-- Cabeçalho do pedido --}}
                    <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm text-gray-500">
                                Pedido <span class="font-semibold text-gray-800">#{{ $p->id }}</span>
                                • {{ $p->data_pedido?->format('d/m/Y') ?? '-' }}
                                • <span
                                    class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ $p->status }}</span>
                            </div>
                            <div class="text-gray-800 font-semibold truncate">
                                Cliente: {{ $p->cliente->nome ?? '-' }}
                                @if (!empty($p->revendedora))
                                    <span class="text-gray-400 font-normal">•</span>
                                    Rev: {{ $p->revendedora->nome }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Forma: {{ $p->forma->nome ?? '-' }} • Plano: {{ $p->plano->descricao ?? '-' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 text-right">
                            <div>
                                <div class="text-[11px] text-gray-500">Líquido</div>
                                <div class="font-semibold text-gray-800">
                                    {{ $fmt($calc['receita_liquida_itens'] ?? ($p->valor_liquido ?? 0)) }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-gray-500">Custo</div>
                                <div class="font-semibold text-gray-800">{{ $fmt($calc['custo_total'] ?? 0) }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-gray-500">Lucro</div>
                                <div
                                    class="font-semibold {{ ($calc['lucro'] ?? 0) < 0 ? 'text-red-600' : 'text-green-700' }}">
                                    {{ $fmt($calc['lucro'] ?? 0) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] text-gray-500">Margem</div>
                                <div class="font-semibold text-gray-800">{{ $fmtPerc($calc['margem'] ?? 0) }}</div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('vendas.show', $p->id) }}"
                                    class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs">
                                    Ver pedido
                                </a>
                                <button @click="open = !open"
                                    class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-xs">
                                    <span x-text="open ? 'Ocultar itens' : 'Ver itens'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Itens (detalhado igual show) --}}
                    <div x-show="open" x-collapse class="border-t">
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Produto</th>
                                        <th class="text-left px-3 py-2">Cod</th>
                                        <th class="text-right px-3 py-2">Qtd</th>
                                        <th class="text-right px-3 py-2">Venda Unit</th>
                                        <th class="text-right px-3 py-2">Total</th>
                                        <th class="text-right px-3 py-2">Desc</th>
                                        <th class="text-right px-3 py-2">Líquido</th>
                                        <th class="text-right px-3 py-2">Compra Unit</th>
                                        <th class="text-right px-3 py-2">Últ compra</th>
                                        <th class="text-right px-3 py-2">Qtd últ</th>
                                        <th class="text-right px-3 py-2">Custo</th>
                                        <th class="text-right px-3 py-2">Lucro</th>
                                        <th class="text-right px-3 py-2">Margem</th>
                                        <th class="text-right px-3 py-2">Pts</th>
                                        <th class="text-right px-3 py-2">Pts Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($p->itens as $it)
                                        @php
                                            $qtd = (float) ($it->quantidade ?? 0);
                                            $total = (float) ($it->preco_total ?? 0);
                                            $desc = (float) ($it->valor_desconto ?? 0);
                                            $liq = $total - $desc;

                                            $compraUnit = (float) ($it->produto->preco_compra ?? 0);
                                            $custo = $qtd * $compraUnit;

                                            $lucro = $liq - $custo;
                                            $margem = $liq > 0 ? ($lucro / $liq) * 100 : 0;

                                            $ultData = $it->ultima_compra_data ?? null;
                                            $ultQtd = $it->ultima_compra_qtd ?? null;
                                        @endphp

                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 font-semibold text-gray-800">
                                                {{ $it->produto->nome ?? 'Produto #' . $it->produto_id }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600">{{ $it->codfabnumero ?? '-' }}</td>
                                            <td class="px-3 py-2 text-right">{{ (int) $qtd }}</td>
                                            <td class="px-3 py-2 text-right">{{ $fmt($it->preco_unitario ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right">{{ $fmt($total) }}</td>
                                            <td class="px-3 py-2 text-right">{{ $fmt($desc) }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">{{ $fmt($liq) }}</td>
                                            <td class="px-3 py-2 text-right">{{ $fmt($compraUnit) }}</td>
                                            <td class="px-3 py-2 text-right">
                                                {{ $ultData ? \Carbon\Carbon::parse($ultData)->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                {{ is_null($ultQtd) ? '-' : (int) $ultQtd }}</td>
                                            <td class="px-3 py-2 text-right">{{ $fmt($custo) }}</td>
                                            <td
                                                class="px-3 py-2 text-right font-semibold {{ $lucro < 0 ? 'text-red-600' : 'text-green-700' }}">
                                                {{ $fmt($lucro) }}
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ $fmtPerc($margem) }}</td>
                                            <td class="px-3 py-2 text-right">{{ (int) ($it->pontuacao ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right">{{ (int) ($it->pontuacao_total ?? 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="text-[11px] text-gray-500 mt-3">
                                Pedido #{{ $p->id }} • Itens: {{ (int) ($calc['qtd_itens'] ?? 0) }}
                                • Pontos: {{ (int) ($calc['pontos'] ?? 0) }}
                                • Pontos total: {{ (int) ($calc['pontos_total'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
                    Nenhuma venda encontrada para o período.
                </div>
            @endforelse
        </div>

        <div class="p-2">
            {{ $pedidos->links() }}
        </div>
    </div>
</x-app-layout>
