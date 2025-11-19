<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Produtos Comprados - {{ $cliente->nome ?? 'Cliente #' . $cliente->id }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-4">
        {{-- Cabeçalho / atalhos --}}
        <div class="flex flex-wrap justify-between items-center gap-2">
            <div class="text-sm text-gray-600">
                <span class="font-semibold">Cliente:</span>
                {{ $cliente->nome ?? '-' }}
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('clientes.index') }}"
                   class="px-3 py-1 border rounded hover:bg-gray-50">
                    Voltar para Clientes
                </a>
                <a href="{{ route('relatorios.recebimentos.extrato_cliente', ['cliente_id' => $cliente->id]) }}"
                   class="px-3 py-1 border rounded hover:bg-gray-50">
                    Extrato Financeiro
                </a>
                <a href="{{ route('relatorios.clientes.extrato_pedidos', ['cliente' => $cliente->id]) }}"
                   class="px-3 py-1 border rounded hover:bg-gray-50">
                    Extrato de Pedidos
                </a>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET"
              class="bg-white border rounded p-4 text-sm space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status do Pedido</label>
                    @php $st = $filtros['status'] ?? 'ENTREGUE'; @endphp
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="TODOS"     @selected($st === 'TODOS')>Todos</option>
                        <option value="PENDENTE"  @selected($st === 'PENDENTE')>Pendente</option>
                        <option value="ABERTO"    @selected($st === 'ABERTO')>Aberto</option>
                        <option value="ENTREGUE"  @selected($st === 'ENTREGUE')>Entregue</option>
                        <option value="CANCELADO" @selected($st === 'CANCELADO')>Cancelado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Data Inicial</label>
                    <input type="date" name="data_de"
                           value="{{ $filtros['data_de'] ?? '' }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Data Final</label>
                    <input type="date" name="data_ate"
                           value="{{ $filtros['data_ate'] ?? '' }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('relatorios.clientes.extrato_produtos', ['cliente' => $cliente->id]) }}"
                   class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Limpar
                </a>
                <button type="submit"
                        class="px-4 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Resumo --}}
        @php
            $qtdItens  = $resumo->qtd_itens ?? 0;
            $qtdTotal  = $resumo->qtd_total ?? 0;
            $vlrTotal  = $resumo->valor_total ?? 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                <div class="text-xs text-gray-500 uppercase font-semibold">Qtd. Produtos Diferentes</div>
                <div class="text-base font-bold text-blue-700">
                    {{ $qtdItens }}
                </div>
            </div>
            <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                <div class="text-xs text-gray-500 uppercase font-semibold">Qtd. Total (soma das unidades)</div>
                <div class="text-base font-bold text-indigo-700">
                    {{ number_format($qtdTotal, 0, ',', '.') }}
                </div>
            </div>
            <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                <div class="text-xs text-gray-500 uppercase font-semibold">Valor Total</div>
                <div class="text-base font-bold text-emerald-700">
                    R$ {{ number_format($vlrTotal, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabela de produtos --}}
        <div class="bg-white border rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-3 py-2 text-left">Cód. Fab</th>
                        <th class="px-3 py-2 text-left">Produto</th>
                        <th class="px-3 py-2 text-right">Qtd Total</th>
                        <th class="px-3 py-2 text-right">Valor Total (R$)</th>
                        <th class="px-3 py-2 text-left">1ª Compra</th>
                        <th class="px-3 py-2 text-left">Última Compra</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($produtos as $p)
                        <tr>
                            <td class="px-3 py-2">
                                {{ $p->codfabnumero ?? '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $p->produto_nome ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float)($p->qtd_total ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format((float)($p->valor_total ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2">
                                @if(!empty($p->primeira_compra))
                                    {{ \Carbon\Carbon::parse($p->primeira_compra)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if(!empty($p->ultima_compra))
                                    {{ \Carbon\Carbon::parse($p->ultima_compra)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                                Nenhum produto encontrado para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
