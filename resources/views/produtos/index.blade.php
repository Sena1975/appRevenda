{{-- resources/views/produtos/index.blade.php --}}
@extends('layouts.app')

@section('header')
    <h1 class="text-xl font-semibold text-gray-800">Produtos</h1>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        {{-- Barra superior: título + Ações --}}
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-500">
                Lista de produtos com filtros e paginação.
            </div>
            <a href="{{ route('produtos.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Novo Produto
            </a>
        </div>

        {{-- Alertas --}}
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- FILTROS (sem quebra; scroll horizontal no mobile) --}}
        <form method="GET" action="{{ route('produtos.index') }}" class="mb-4">
            <div class="overflow-x-auto">
                <div class="min-w-max flex flex-nowrap items-end gap-3 pr-2">
                    {{-- Produto / Código --}}
                    <div class="flex flex-col shrink-0">
                        <label for="busca" class="text-sm font-medium text-gray-700">Produto / Código</label>
                        <input type="text" name="busca" id="busca"
                            class="h-10 w-56 border-gray-300 rounded-md shadow-sm" value="{{ request('busca') }}"
                            placeholder="Ex.: Kajal, 1234...">
                    </div>

                    {{-- Categoria --}}
                    <div class="flex flex-col shrink-0">
                        <label for="categoria_id" class="text-sm font-medium text-gray-700">Categoria</label>
                        <select name="categoria_id" id="categoria_id"
                            class="h-10 w-72 border-gray-300 rounded-md shadow-sm">
                            <option value="">Todas</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ (string) $cat->id === (string) request('categoria_id') ? 'selected' : '' }}>
                                    {{ $cat->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Itens por página --}}
                    <div class="flex flex-col shrink-0">
                        <label for="por_pagina" class="text-sm font-medium text-gray-700">Itens por página</label>
                        <select name="por_pagina" id="por_pagina" class="h-10 w-36 border-gray-300 rounded-md shadow-sm"
                            onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}"
                                    {{ (int) request('por_pagina', 10) === $n ? 'selected' : '' }}>{{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ações --}}
                    <div class="flex items-end gap-2 shrink-0">
                        <button type="submit"
                            class="h-10 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 text-white hover:bg-blue-700 transition">
                            Filtrar
                        </button>
                        <a href="{{ route('produtos.index') }}"
                            class="h-10 inline-flex items-center gap-2 rounded-md border px-4 text-gray-700 hover:bg-gray-50 transition"
                            title="Limpar filtros">
                            Limpar
                        </a>
                    </div>
                </div>
            </div>
        </form>



        {{-- TABELA --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Imagem</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Nome</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Código</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Categoria</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Subcategoria</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fornecedor</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600 w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                @if ($p->imagem)
                                    <img src="{{ asset($p->imagem) }}" alt="{{ $p->nome }}"
                                        class="h-10 w-10 rounded object-cover">
                                @else
                                    <div
                                        class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center text-xs text-gray-500">
                                        N/A</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $p->nome }}</td>
                            <td class="px-3 py-2">
                                <div class="text-xs text-gray-500 leading-tight">
                                    <div><span class="font-medium text-gray-700">codfab:</span> {{ $p->codfab }}</div>
                                    <div><span class="font-medium text-gray-700">num:</span> {{ $p->codfabnumero }}</div>
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ $p->categoria->nome ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $p->subcategoria->nome ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $p->fornecedor->nome ?? '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('produtos.edit', $p) }}"
                                        class="rounded border px-2 py-1 hover:bg-gray-50">Editar</a>
                                    <form action="{{ route('produtos.destroy', $p) }}" method="POST"
                                        onsubmit="return confirm('Excluir este produto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="rounded border px-2 py-1 text-red-600 hover:bg-red-50">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINAÇÃO (preserva filtros) --}}
        <div class="mt-4">
            {{ $produtos->appends(request()->except('page'))->links() }}
        </div>
    </div>
@endsection
