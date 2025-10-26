<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Tabela de Preços</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Preços</h3>
            <a href="{{ route('tabelapreco.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Novo Preço
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Produto</th>
                    <th class="px-4 py-2 text-left">Preço Revenda</th>
                    <th class="px-4 py-2 text-left">Pontuação</th>
                    <th class="px-4 py-2 text-left">Data Início</th>
                    <th class="px-4 py-2 text-left">Data Fim</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tabelas as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $item->produto->nome ?? '—' }}</td>
                        <td class="px-4 py-2">R$ {{ number_format($item->preco_revenda, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ $item->pontuacao }}</td>
                        <td class="px-4 py-2">{{ $item->data_inicio }}</td>
                        <td class="px-4 py-2">{{ $item->data_fim }}</td>
                        <td class="px-4 py-2">
                            {{ $item->status ? 'Ativo' : 'Inativo' }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('tabelapreco.edit', $item->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('tabelapreco.destroy', $item->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Excluir este registro?')">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
