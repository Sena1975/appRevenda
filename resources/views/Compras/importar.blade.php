<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Importar Itens - Pedido #{{ $pedido->id }}
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-2xl mx-auto">
        <form action="{{ route('compras.processarImportacao', $pedido->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o arquivo CSV</label>
                <input type="file" name="arquivo" accept=".csv,.txt"
                       class="w-full border-gray-300 rounded-md shadow-sm" required>
                <p class="text-xs text-gray-500 mt-1">
                    O arquivo deve ter o formato: <b>codfab;quantidade</b> (um item por linha)
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('compras.index') }}" 
                   class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</a>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Importar Itens
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
