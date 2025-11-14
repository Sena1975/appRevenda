<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Estoque Atual
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">

        {{-- Mensagens de sessão --}}
        @if (session('success'))
            <div class="mb-3 p-3 rounded bg-green-100 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-3 p-3 rounded bg-red-100 text-red-800 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filtros --}}
        <form method="GET" action="{{ route('estoque.index') }}" class="mb-4 border rounded-lg p-4 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                {{-- Produto --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Produto (código ou descrição)
                    </label>
                    <input type="text" name="produto" value="{{ request('produto') }}"
                        class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm"
                        placeholder="Ex.: 237283 ou Perfume Essencial">
                </div>

                {{-- Apenas com estoque --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Opções
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="somente_com_estoque" name="somente_com_estoque" value="1"
                            class="rounded border-gray-300" @checked($somenteComEstoque)>
                        <label for="somente_com_estoque" class="text-sm text-gray-700">
                            Mostrar apenas com estoque &gt; 0
                        </label>

                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md shadow text-sm hover:bg-blue-700">
                    Filtrar
                </button>

                <a href="{{ route('estoque.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md shadow-sm text-sm hover:bg-gray-100">
                    Limpar
                </a>

                <a href="{{ route('movestoque.index') }}"
                    class="ml-auto px-4 py-2 border border-gray-300 text-gray-700 rounded-md shadow-sm text-sm hover:bg-gray-100">
                    Ver Movimentação
                </a>
            </div>
        </form>

        {{-- Tabela de estoque --}}
        <div class="overflow-x-auto border rounded-lg">
            @php
                // Mantém todos os filtros atuais
                $baseQuery = request()->all();

                // Se o filtro "somenteComEstoque" estiver ativo no backend,
                // garante que o parâmetro entra na URL dos cabeçalhos
                if (!array_key_exists('somente_com_estoque', $baseQuery) && !empty($somenteComEstoque)) {
                    $baseQuery['somente_com_estoque'] = 1;
                }
            @endphp
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        {{-- Código --}}
                        <th class="px-3 py-2 text-left w-28">
                            @php
                                $isCurrent = $sort === 'codigo';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'codigo', 'dir' => $newDir])) }}"
                                class="flex items-center gap-1">
                                Código
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>

                        </th>
                        {{-- Descrição --}}
                        <th class="px-3 py-2 text-left">
                            @php
                                $isCurrent = $sort === 'descricao';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'descricao', 'dir' => $newDir])) }}"
                                class="flex items-center gap-1">
                                Descrição
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Categoria --}}
                        <th class="px-3 py-2 text-left w-32">
                            @php
                                $isCurrent = $sort === 'categoria';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'categoria', 'dir' => $newDir])) }}"
                                class="flex items-center gap-1">
                                Categoria
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Estoque --}}
                        <th class="px-3 py-2 text-right w-24">
                            @php
                                $isCurrent = $sort === 'estoque';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'estoque', 'dir' => $newDir])) }}"
                                class="flex items-center justify-end gap-1">
                                Estoque
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Preço Compra --}}
                        <th class="px-3 py-2 text-right w-28">
                            @php
                                $isCurrent = $sort === 'preco_compra';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'preco_compra', 'dir' => $newDir])) }}"
                                class="flex items-center justify-end gap-1">
                                Preço Compra
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Preço Revenda --}}
                        <th class="px-3 py-2 text-right w-28">
                            @php
                                $isCurrent = $sort === 'preco_revenda';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'preco_revenda', 'dir' => $newDir])) }}"
                                class="flex items-center justify-end gap-1">
                                Preço Revenda
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Lucro Unitário (não ordenado no banco, só visual) --}}
                        <th class="px-3 py-2 text-right w-28">
                            Lucro Unit.
                        </th>

                        {{-- Última Entrada --}}
                        <th class="px-3 py-2 text-left w-36">
                            @php
                                $isCurrent = $sort === 'ultima_entrada';
                                $newDir = $isCurrent && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('estoque.index', array_merge($baseQuery, ['sort' => 'ultima_entrada', 'dir' => $newDir])) }}"
                                class="flex items-center gap-1">
                                Última Entrada
                                @if ($isCurrent)
                                    <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        <th class="px-3 py-2 text-center w-32">Ações</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($itens as $item)
                        @php
                            $precoCompra = (float) ($item->preco_compra ?? 0);
                            $precoRevenda = (float) ($item->preco_revenda ?? 0);
                            $lucro = $precoRevenda - $precoCompra;
                            $qtd = (float) ($item->qtd_estoque ?? 0);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                {{ $item->codigo_fabrica }}
                            </td>

                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-800">
                                    {{ $item->descricao_produto }}
                                </div>
                                @if (!empty($item->subcategoria))
                                    <div class="text-xs text-gray-500">
                                        {{ $item->subcategoria }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                {{ $item->categoria ?? '-' }}
                            </td>

                            <td class="px-3 py-2 text-right">
                                <span class="@if ($qtd <= 0) text-red-600 font-semibold @endif">
                                    {{ number_format($qtd, 2, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format($precoCompra, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format($precoRevenda, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2 text-right">
                                R$ {{ number_format($lucro, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2">
                                @if ($item->data_ultima_entrada)
                                    {{ \Carbon\Carbon::parse($item->data_ultima_entrada)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('movestoque.index', ['produto' => $item->codigo_fabrica]) }}"
                                    class="px-2 py-1 text-xs rounded border border-blue-500 text-blue-600 hover:bg-blue-50">
                                    Movimentações
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-gray-500">
                                Nenhum item encontrado para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if (method_exists($itens, 'links'))
            <div class="mt-4">
                {{ $itens->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
