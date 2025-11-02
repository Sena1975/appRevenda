{{-- resources/views/produtos/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold text-gray-800">Produtos</h1>
            <a href="{{ route('produtos.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Novo Produto
            </a>
        </div>

        {{-- Mensagem de sucesso --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

{{-- 🔍 Filtros --}}
<form method="GET" action="{{ route('produtos.index') }}" 
      class="mb-4 flex flex-wrap items-center justify-between gap-3">

    {{-- Campo de busca (um pouco menor) --}}
    <div class="flex-1 min-w-[250px] max-w-[400px]">
        <input type="text" name="busca" value="{{ request('busca') }}"
               placeholder="Buscar por nome ou código..."
               class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    {{-- Linha direita: categoria, itens por página e botões --}}
    <div class="flex flex-wrap items-center gap-2 justify-end">

        {{-- Filtro por categoria (aumentado) --}}
        <select name="categoria_id"
                class="border border-gray-300 rounded-md shadow-sm px-3 py-2 min-w-[250px] focus:ring-blue-500 focus:border-blue-500">
            <option value="">Todas as Categorias</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}" 
                    {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                    {{ $categoria->nome }}
                </option>
            @endforeach
        </select>

        {{-- Itens por página --}}
        <select name="por_pagina"
                class="border border-gray-300 rounded-md shadow-sm px-3 py-2 w-28 text-center focus:ring-blue-500 focus:border-blue-500">
            <option value="5"  {{ request('por_pagina') == 5  ? 'selected' : '' }}>5 / pág</option>
            <option value="10" {{ request('por_pagina', 10) == 10 ? 'selected' : '' }}>10 / pág</option>
            <option value="20" {{ request('por_pagina') == 20 ? 'selected' : '' }}>20 / pág</option>
            <option value="50" {{ request('por_pagina') == 50 ? 'selected' : '' }}>50 / pág</option>
            <option value="100" {{ request('por_pagina') == 100 ? 'selected' : '' }}>100 / pág</option>
        </select>

        {{-- Botão Filtrar --}}
        <button type="submit" 
                class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md shadow transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Filtrar
        </button>

        {{-- Botão Limpar (forçado para não ficar branco) --}}
        <a href="{{ route('produtos.index') }}"
           class="inline-flex items-center gap-1 font-medium px-4 py-2 rounded-md shadow transition"
           style="background-color:#4B5563; color:#FFFFFF;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#F472B6;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpar
        </a>
    </div>
</form>

        <script>
            document.querySelectorAll('select[name="categoria_id"], select[name="por_pagina"]').forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        </script>

        {{-- Tabela de produtos --}}
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded-lg text-sm">
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
                    @forelse ($produtos as $produto)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-4">{{ $produto->id }}</td>
                            <td class="py-2 px-4">{{ $produto->nome }}</td>
                            <td class="py-2 px-4">
                                @if ($produto->imagem)
                                    <img src="{{ asset($produto->imagem) }}" alt="Imagem"
                                        class="h-10 w-10 rounded object-cover">
                                @else
                                    <span class="text-gray-400 text-xs">Sem imagem</span>
                                @endif
                            </td>
                            <td class="py-2 px-4">{{ $produto->categoria->nome ?? '-' }}</td>
                            <td class="py-2 px-4">{{ $produto->subcategoria->nome ?? '-' }}</td>
                            <td class="py-2 px-4">{{ $produto->fornecedor->nomefantasia ?? '-' }}</td>
                            <td class="py-2 px-4">
                                <span
                                    class="px-2 py-1 rounded text-white {{ $produto->status ? 'bg-green-500' : 'bg-red-500' }}">
                                    {{ $produto->status ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="py-2 px-4 text-center space-x-2">
                                <a href="{{ route('produtos.edit', $produto->id) }}"
                                    class="text-blue-600 hover:underline">Editar</a>
                                <form action="{{ route('produtos.destroy', $produto->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 px-4 text-center text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $produtos->appends(request()->except('page'))->links() }}
        </div>
    </div>
@endsection
