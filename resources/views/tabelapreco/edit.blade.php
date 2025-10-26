<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Tabela de Preço</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('tabelapreco.update', $tabela->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <!-- Produto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produto_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}" {{ $tabela->produto_id == $produto->id ? 'selected' : '' }}>
                                {{ $produto->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Preço de Revenda -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Revenda</label>
                    <input type="number" step="0.01" name="preco_revenda"
                        value="{{ $tabela->preco_revenda }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- Pontuação -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pontuação</label>
                    <input type="number" name="pontuacao" value="{{ $tabela->pontuacao }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Data início -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" name="data_inicio" value="{{ $tabela->data_inicio }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Data fim -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" name="data_fim" value="{{ $tabela->data_fim }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $tabela->status ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ !$tabela->status ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <a href="{{ route('tabelapreco.index') }}" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Atualizar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
