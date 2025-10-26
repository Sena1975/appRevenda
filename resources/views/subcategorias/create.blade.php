<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Subcategoria</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        <form action="{{ route('subcategorias.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição (opcional)</label>
                    <input type="text" name="subcategoria" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="flex justify-end space-x-2 mt-4">
                    <a href="{{ route('subcategorias.index') }}"
                       class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
