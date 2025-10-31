<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Ajustar Estoque</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        <form action="{{ route('estoque.update', $estoque->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <input type="text" value="{{ $estoque->produto->nome ?? '' }}"
                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código Fábrica</label>
                    <input type="text" value="{{ $estoque->codfabnumero ?? '' }}"
                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estoque Gerencial</label>
                    <input type="number" step="0.01" name="estoque_gerencial" value="{{ $estoque->estoque_gerencial }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reservado</label>
                    <input type="number" step="0.01" name="reservado" value="{{ $estoque->reservado }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Avaria</label>
                    <input type="number" step="0.01" name="avaria" value="{{ $estoque->avaria }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('estoque.index') }}"
                   class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Voltar</a>
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Salvar Alterações</button>
            </div>
        </form>
    </div>
</x-app-layout>
