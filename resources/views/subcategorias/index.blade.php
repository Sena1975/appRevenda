<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Subcategorias</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
        <div class="flex justify-between mb-4">
            <a href="{{ route('subcategorias.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Nova Subcategoria
            </a>
        </div>

        <table class="min-w-full border border-gray-200">
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
                @foreach ($subcategorias as $sub)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $sub->id }}</td>
                        <td class="px-4 py-2">{{ $sub->nome }}</td>
                        <td class="px-4 py-2">{{ $sub->categoria->nome ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $sub->status ? 'Ativa' : 'Inativa' }}</td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('subcategorias.edit', $sub->id) }}" class="text-blue-600 hover:underline">Editar</a> |
                            <form action="{{ route('subcategorias.destroy', $sub->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline"
                                    onclick="return confirm('Deseja realmente excluir?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
