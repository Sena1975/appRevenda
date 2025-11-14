<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Movimentação de Estoque
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
        <form method="GET" action="{{ route('movestoque.index') }}" class="mb-4 border rounded-lg p-4 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">

                {{-- Produto (código/descrição) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Produto (código ou descrição)
                    </label>
                    <input type="text" name="produto" value="{{ request('produto') }}"
                        class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm"
                        placeholder="Ex.: 237283 ou Hidratante Natura">
                </div>

                {{-- Tipo (ENTRADA / SAIDA) --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Tipo
                    </label>
                    <select name="tipo" class="w-full border-gray-300 rounded-md shadow-sm px-2 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($tipos as $t)
                            <option value="{{ $t }}" @selected(request('tipo') === $t)>
                                {{ $t }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Origem (COMPRA / VENDA / AJUSTE) --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Origem
                    </label>
                    <select name="origem" class="w-full border-gray-300 rounded-md shadow-sm px-2 py-2 text-sm">
                        <option value="">Todas</option>
                        @foreach ($origens as $o)
                            <option value="{{ $o }}" @selected(request('origem') === $o)>
                                {{ $o }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Período de datas --}}
                <div class="md:col-span-2">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Data inicial
                            </label>
                            <input type="date" name="data_ini" value="{{ $dataIni }}"
                                class="w-full border-gray-300 rounded-md shadow-sm px-2 py-2 text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Data final
                            </label>
                            <input type="date" name="data_fim" value="{{ $dataFim }}"
                                class="w-full border-gray-300 rounded-md shadow-sm px-2 py-2 text-sm">
                        </div>
                    </div>
                </div>


            </div>

            <div class="mt-4 flex items-center gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md shadow text-sm hover:bg-blue-700">
                    Filtrar
                </button>

                <a href="{{ route('movestoque.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md shadow-sm text-sm hover:bg-gray-100">
                    Limpar
                </a>

                <a href="{{ route('estoque.index') }}"
                    class="ml-auto px-4 py-2 border border-gray-300 text-gray-700 rounded-md shadow-sm text-sm hover:bg-gray-100">
                    ← Voltar para Estoque
                </a>
            </div>
        </form>

        {{-- Tabela de movimentos --}}
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">Data</th>
                        <th class="px-3 py-2 text-left">Produto</th>
                        <th class="px-3 py-2 text-left">Tipo</th>
                        <th class="px-3 py-2 text-left">Origem</th>
                        <th class="px-3 py-2 text-right">Quantidade</th>
                        <th class="px-3 py-2 text-left">Observação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($movimentos as $mov)
                        <tr class="hover:bg-gray-50">
                            {{-- Data --}}
                            <td class="px-3 py-2">
                                @if ($mov->data_mov)
                                    {{ \Carbon\Carbon::parse($mov->data_mov)->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Produto (código + descrição) --}}
                            <td class="px-3 py-2">
                                @if ($mov->produto)
                                    <div class="font-semibold text-gray-800">
                                        {{ $mov->produto->nome }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Cód: {{ $mov->produto->codfabnumero ?? $mov->produto->id }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">Produto não vinculado</span>
                                @endif
                            </td>

                            {{-- Tipo --}}
                            <td class="px-3 py-2">
                                <span
                                    class="px-2 py-1 rounded text-xs
                                    @if ($mov->tipo_mov === 'ENTRADA') bg-green-100 text-green-800
                                    @elseif ($mov->tipo_mov === 'SAIDA')
                                        bg-red-100 text-red-800
                                    @else
                                        bg-gray-100 text-gray-700 @endif
                                ">
                                    {{ $mov->tipo_mov ?? '-' }}
                                </span>
                            </td>

                            {{-- Origem --}}
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded text-xs bg-blue-50 text-blue-700">
                                    {{ $mov->origem ?? '-' }}
                                </span>
                            </td>

                            {{-- Quantidade --}}
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) $mov->quantidade, 2, ',', '.') }}
                            </td>

                            {{-- Observação / histórico --}}
                            <td class="px-3 py-2 text-gray-600">
                                {{ $mov->observacao ?? ($mov->historico ?? '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                Nenhuma movimentação encontrada para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if (method_exists($movimentos, 'links'))
            <div class="mt-4">
                {{ $movimentos->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
