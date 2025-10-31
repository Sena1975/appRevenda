<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            {{ isset($forma) ? 'Editar Forma de Pagamento' : 'Nova Forma de Pagamento' }}
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        <form action="{{ isset($forma) ? route('formapagamento.update', $forma->id) : route('formapagamento.store') }}" method="POST">
            @csrf
            @if(isset($forma)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome', $forma->nome ?? '') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Máx. Parcelas</label>
                    <input type="number" name="max_parcelas" value="{{ old('max_parcelas', $forma->max_parcelas ?? 1) }}" min="1" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gera Contas a Receber?</label>
                    <select name="gera_receber" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('gera_receber', $forma->gera_receber ?? 1) == 1 ? 'selected' : '' }}>Sim</option>
                        <option value="0" {{ old('gera_receber', $forma->gera_receber ?? 1) == 0 ? 'selected' : '' }}>Não</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ativo</label>
                    <select name="ativo" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('ativo', $forma->ativo ?? 1) == 1 ? 'selected' : '' }}>Sim</option>
                        <option value="0" {{ old('ativo', $forma->ativo ?? 1) == 0 ? 'selected' : '' }}>Não</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-6">
                <a href="{{ route('formapagamento.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    {{ isset($forma) ? 'Atualizar' : 'Salvar' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
