{{-- resources/views/categorias/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-800">Categorias</h1>
        <a href="{{ route('categorias.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Nova Categoria
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Categoria</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categorias as $categoria)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $categoria->id }}</td>
                        <td class="px-4 py-2">{{ $categoria->nome }}</td>
                        <td class="px-4 py-2">{{ $categoria->categoria }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-white {{ $categoria->status ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $categoria->status ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="{{ route('categorias.edit', $categoria->id) }}"
                               class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('categorias.destroy', $categoria->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Deseja realmente excluir esta categoria?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhuma categoria encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($categorias,'links'))
        <div class="mt-4">
            {{ $categorias->links() }}
        </div>
    @endif
</div>
@endsection
