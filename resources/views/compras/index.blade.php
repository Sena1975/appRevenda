<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Pedidos de Compra</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">


        {{-- Título + botão --}}
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Pedidos</h3>
            <a href="{{ route('compras.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold">
                Novo Pedido
            </a>
        </div>

        {{-- Mensagens de feedback --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-sm">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-sm">{{ session('error') }}</div>
        @endif

        {{-- Filtros --}}
        <form method="GET" action="{{ route('compras.index') }}"
            class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-md text-sm space-y-3">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                {{-- Fornecedor --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}"
                                {{ ($filtros['fornecedor_id'] ?? '') == $fornecedor->id ? 'selected' : '' }}>
                                {{ $fornecedor->nomefantasia ?? ($fornecedor->razaosocial ?? $fornecedor->nome) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        <option value="PENDENTE" {{ ($filtros['status'] ?? '') === 'PENDENTE' ? 'selected' : '' }}>
                            Pendente</option>
                        <option value="RECEBIDA" {{ ($filtros['status'] ?? '') === 'RECEBIDA' ? 'selected' : '' }}>
                            Recebida</option>
                        <option value="CANCELADA" {{ ($filtros['status'] ?? '') === 'CANCELADA' ? 'selected' : '' }}>
                            Cancelada</option>
                    </select>
                </div>

                {{-- Data Inicial --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Data Inicial</label>
                    <input type="date" name="data_ini" value="{{ $filtros['data_ini'] ?? '' }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Data Final --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Data Final</label>
                    <input type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 mt-2">
                <a href="{{ route('compras.index') }}"
                    class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Limpar
                </a>
                <button type="submit"
                    class="px-4 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Resumo (só mostra se tiver pedidos) --}}
        @if ($pedidos->count())
            @php
                $totalCompra = $resumo['total_compra'] ?? 0;
                $totalLiquido = $resumo['total_liquido'] ?? 0;
                $totalVenda = $resumo['total_venda'] ?? 0;
                $lucro = $resumo['lucro'] ?? 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 text-sm">
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Total Compra (Bruto)</div>
                    <div class="text-base font-bold text-blue-700">
                        R$ {{ number_format($totalCompra, 2, ',', '.') }}
                    </div>
                </div>
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Total Líquido</div>
                    <div class="text-base font-bold text-indigo-700">
                        R$ {{ number_format($totalLiquido, 2, ',', '.') }}
                    </div>
                </div>
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Total Venda</div>
                    <div class="text-base font-bold text-green-700">
                        R$ {{ number_format($totalVenda, 2, ',', '.') }}
                    </div>
                </div>
                <div class="border border-gray-200 rounded-md p-3 bg-gray-50">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Lucro</div>
                    <div class="text-base font-bold {{ $lucro >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        R$ {{ number_format($lucro, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabela --}}
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Fornecedor</th>
                    <th class="px-4 py-2 text-left">Nº Pedido</th>
                    <th class="px-4 py-2 text-left">Nota Fiscal</th>
                    <th class="px-4 py-2 text-right">Total Compra</th>
                    <th class="px-4 py-2 text-right">Total Líquido</th>
                    <th class="px-4 py-2 text-right">Total Venda</th>
                    <th class="px-4 py-2 text-right">Lucro</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center w-40">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($pedidos as $pedido)
                    @php
                        $valorTotal = $pedido->valor_total ?? 0;
                        $valorLiquido = $pedido->valor_liquido ?? 0;
                        $totalVenda = $pedido->preco_venda_total ?? 0;
                        $lucroLinha = $totalVenda - $valorLiquido;
                    @endphp

                    <tr>
                        <td class="px-4 py-2">{{ $pedido->id }}</td>

                        <td class="px-4 py-2">
                            {{ $pedido->fornecedor->nomefantasia ?? ($pedido->fornecedor->razaosocial ?? '-') }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $pedido->numpedcompra ?? '-' }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $pedido->numero_nota ?? '-' }}
                        </td>

                        <td class="px-4 py-2 text-right text-blue-700">
                            R$ {{ number_format($valorTotal, 2, ',', '.') }}
                        </td>

                        <td class="px-4 py-2 text-right text-indigo-700">
                            R$ {{ number_format($valorLiquido, 2, ',', '.') }}
                        </td>

                        <td class="px-4 py-2 text-right text-green-700">
                            R$ {{ number_format($totalVenda, 2, ',', '.') }}
                        </td>

                        <td class="px-4 py-2 text-right {{ $lucroLinha >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            R$ {{ number_format($lucroLinha, 2, ',', '.') }}
                        </td>

                        <td class="px-4 py-2 text-center">
                            @if ($pedido->status === 'PENDENTE')
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pendente</span>
                            @elseif($pedido->status === 'RECEBIDA')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Recebida</span>
                            @elseif($pedido->status === 'CANCELADA')
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Cancelada</span>
                            @endif
                        </td>

                        <td class="border px-4 py-2 text-center">
                            <div class="flex justify-center space-x-2">
                                {{-- Visualizar --}}
                                <a href="{{ route('compras.show', $pedido->id) }}"
                                    class="text-blue-600 hover:text-blue-800" title="Visualizar">
                                    🔍
                                </a>

                                {{-- Editar --}}
                                <a href="{{ route('compras.edit', $pedido->id) }}"
                                    class="text-orange-500 hover:text-orange-700" title="Editar">
                                    ✏️
                                </a>

                                {{-- Importar Itens --}}
                                <a href="{{ route('compras.importar', $pedido->id) }}"
                                    class="text-green-600 hover:text-green-800" title="Importar Itens">
                                    📦
                                </a>

                                {{-- Exportar Itens --}}
                                <a href="{{ route('compras.exportar', $pedido->id) }}"
                                    class="text-indigo-600 hover:text-indigo-800" title="Exportar Itens">
                                    📤
                                </a>

                                {{-- Excluir / Cancelar --}}
                                <button type="button" class="text-red-600 hover:text-red-800" title="Cancelar Pedido"
                                    onclick="abrirModalCancelar({{ $pedido->id }}, '{{ $pedido->numpedcompra ?? '' }}')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- agora são 10 colunas --}}
                        <td colspan="10" class="text-center py-4 text-gray-500">
                            Nenhum pedido encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal de Confirmação -->
    <div id="modalCancelar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-96">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Cancelar Pedido</h3>
            <p class="text-sm text-gray-600 mb-3">
                Confirme o cancelamento do pedido <strong id="pedidoNumero"></strong> e informe o motivo:
            </p>

            <form id="formCancelar" method="POST">
                @csrf
                @method('DELETE')

                <textarea name="motivo_cancelamento" rows="3"
                    class="w-full border-gray-300 rounded-md shadow-sm mb-4 text-sm p-2"
                    placeholder="Ex: Pedido duplicado, erro de digitação, produto incorreto..." required></textarea>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalCancelar()"
                        class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">
                        Voltar
                    </button>
                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                        Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalCancelar(id, numero) {
            const modal = document.getElementById('modalCancelar');
            const form = document.getElementById('formCancelar');
            const numeroSpan = document.getElementById('pedidoNumero');

            form.action = `/compras/${id}`;
            numeroSpan.textContent = numero ? `#${numero}` : `#${id}`;

            modal.classList.remove('hidden');
        }

        function fecharModalCancelar() {
            document.getElementById('modalCancelar').classList.add('hidden');
        }
    </script>
</x-app-layout>
