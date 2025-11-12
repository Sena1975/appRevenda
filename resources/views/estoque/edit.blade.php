<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Ajustar Estoque</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto mt-4">
        <form action="{{ route('estoque.update', $estoque->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Produto --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <input type="text"
                           value="{{ $estoque->produto->nome ?? '' }}"
                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                           readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cód. fábrica</label>
                    <input type="text"
                           value="{{ $estoque->codfabnumero }}"
                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                           readonly>
                </div>
            </div>

            {{-- Campos numéricos --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estoque gerencial</label>
                    <input type="number" step="0.001" name="estoque_gerencial"
                           value="{{ old('estoque_gerencial', $estoque->estoque_gerencial) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('estoque_gerencial')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reservado</label>
                    <input type="number" step="0.001" name="reservado"
                           value="{{ old('reservado', $estoque->reservado) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('reservado')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Avaria</label>
                    <input type="number" step="0.001" name="avaria"
                           value="{{ old('avaria', $estoque->avaria) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('avaria')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('estoque.index') }}"
                   class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">
                    Voltar
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
