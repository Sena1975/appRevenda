<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Nova Movimentação de Estoque</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('movestoque.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produto_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($produtos as $produto)
                            <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo de Movimento</label>
                    <select name="tipo_mov" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        <option value="ENTRADA">Entrada</option>
                        <option value="SAIDA">Saída</option>
                        <option value="RESERVA_ENTRADA">Reserva (Entrada)</option>
                        <option value="RESERVA_SAIDA">Reserva (Saída)</option>
                        <option value="AJUSTE">Ajuste Manual</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                    <input type="number" step="0.01" name="quantidade" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço Unitário</label>
                    <input type="number" step="0.01" name="preco_unitario" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Observação</label>
                    <input type="text" name="observacao" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('movestoque.index') }}"
                   class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</a>
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
