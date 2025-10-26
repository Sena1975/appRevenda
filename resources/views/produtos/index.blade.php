<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Produtos</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

    <div class="flex justify-end mb-4">
            <a href="{{ route('produtos.create') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Novo Produto
            </a>        
    </div>


        <table class="min-w-full bg-white border rounded-lg">
            <thead class="bg-gray-200 text-gray-600">
                <tr>
                    <th class="py-2 px-4 text-left">#</th>
                    <th class="py-2 px-4 text-left">Nome</th>
                    <th class="py-2 px-4 text-left">Imagem</th>
                    <th class="py-2 px-4 text-left">Categoria</th>
                    <th class="py-2 px-4 text-left">Subcategoria</th>
                    <th class="py-2 px-4 text-left">Fornecedor</th>
                    <th class="py-2 px-4 text-left">Status</th>
                    <th class="py-2 px-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($produtos as $produto)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4">{{ $produto->id }}</td>
                        <td class="py-2 px-4">{{ $produto->nome }}</td>
                        <td class="py-2 px-4">
                            @if($produto->imagem)
                                <img src="{{ asset($produto->imagem) }}" alt="Imagem" class="h-10 w-10 rounded object-cover">
                            @else
                                <span class="text-gray-400 text-sm">Sem imagem</span>
                            @endif
                        </td>                        
                        <td class="py-2 px-4">{{ $produto->categoria->nome ?? '-' }}</td>
                        <td class="py-2 px-4">{{ $produto->subcategoria->nome ?? '-' }}</td>
                        <td class="py-2 px-4">{{ $produto->fornecedor->nomefantasia ?? '-' }}</td>
                        <td class="py-2 px-4">
                            <span class="px-2 py-1 rounded text-white {{ $produto->status ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $produto->status ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="py-2 px-4 text-center">
                            <a href="{{ route('produtos.edit', $produto->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('produtos.destroy', $produto->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-2"
                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $produtos->links() }}
        </div>
    </div>
</x-app-layout>
