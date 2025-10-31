<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Plano de Pagamento</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('planopagamento.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código</label>
                    <input type="text" name="codplano" value="{{ old('codplano') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input type="text" name="descricao" value="{{ old('descricao') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
                    <select name="formapagamento_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        @foreach($formas as $forma)
                            <option value="{{ $forma->id }}">{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Parcelas</label>
                    <input type="number" name="parcelas" value="1" class="w-full border-gray-300 rounded-md shadow-sm" min="1" required>
                </div>

                <div class="flex items-center mt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="ativo" value="1" class="mr-2" checked>
                        Ativo
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prazo 1 (dias)</label>
                    <input type="number" name="prazo1" value="0" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prazo 2 (dias)</label>
                    <input type="number" name="prazo2" value="0" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prazo 3 (dias)</label>
                    <input type="number" name="prazo3" value="0" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prazo Médio</label>
                    <input type="number" name="prazomedio" value="0" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('planopagamento.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
        
    </div>
</x-app-layout>
