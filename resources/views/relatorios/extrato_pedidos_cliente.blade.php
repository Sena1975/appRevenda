<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Extrato de Pedidos - {{ $cliente->nome ?? 'Cliente #' . $cliente->id }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-4">

        {{-- Navegação / Atalhos --}}
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
                <a href="{{ route('relatorios.clientes.extrato_produtos', ['cliente' => $cliente->id]) }}"
                   class="px-3 py-1 border rounded hover:bg-gray-50">
                    Extrato de Produtos
                </a>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET"
              class="bg-white border rounded p-4 text-sm space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    @php $st = $filtros['status'] ?? 'TODOS'; @endphp
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
                <a href="{{ route('relatorios.clientes.extrato_pedidos', ['cliente' => $cliente->id]) }}"
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
        @if($pedidos->count())
            @php
                $qtd    = $resumo->qtd_pedidos ?? 0;
                $tBruto = $resumo->total_bruto ?? 0;
                $tLiq   = $resumo->total_liquido ?? 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Quantidade de Pedidos</div>
                    <div class="text-base font-bold text-blue-700">
                        {{ $qtd }}
                    </div>
                </div>
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Total Bruto</div>
                    <div class="text-base font-bold text-indigo-700">
                        R$ {{ number_format($tBruto, 2, ',', '.') }}
                    </div>
                </div>
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Total Líquido</div>
                    <div class="text-base font-bold text-emerald-700">
                        R$ {{ number_format($tLiq, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabela de pedidos --}}
        <div class="bg-white border rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Data</th>
                        <th class="px-3 py-2 text-left">Revendedora</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Valor Total</th>
                        <th class="px-3 py-2 text-right">Valor Líquido</th>
                        <th class="px-3 py-2 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pedidos as $p)
                        @php
                            $status = strtoupper((string)$p->status);
                            $badge  = $status === 'ENTREGUE' ? 'bg-green-100 text-green-800'
                                     : ($status === 'CANCELADO' ? 'bg-red-100 text-red-800'
                                     : 'bg-yellow-100 text-yellow-800');
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $p->id }}</td>
                            <td class="px-3 py-2">
                                @if(!empty($p->data_pedido))
                                    {{ \Carbon\Carbon::parse($p->data_pedido)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                {{ $p->revendedora_nome ?? '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $badge }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format((float)($p->valor_total ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format((float)($p->valor_liquido ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('vendas.edit', $p->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs"
                                   title="Abrir pedido">
                                    🔍 Ver Pedido
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-gray-500">
                                Nenhum pedido encontrado para este cliente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($pedidos instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="px-3 py-2">
                    {{ $pedidos->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
