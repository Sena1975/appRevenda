<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Preço de Produto</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('tabelapreco.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produto_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Revenda</label>
                    <input type="number" step="0.01" name="preco_revenda" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Pontuação</label>
                    <input type="number" name="pontuacao" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" name="data_inicio" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" name="data_fim" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <a href="{{ route('tabelapreco.index') }}" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
