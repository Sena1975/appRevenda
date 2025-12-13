<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Importar Produtos</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('arquivo_relatorio'))
            <div class="mb-4">
                <a href="{{ route('produtos.importar.relatorio', session('arquivo_relatorio')) }}"
                    class="text-blue-600 hover:underline">
                    Baixar relatório da importação
                </a>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="text-sm text-gray-600 mb-4">
            Formato esperado (com header): <br>
            <code class="bg-gray-100 px-2 py-1 rounded">codfabnumero,codnotafiscal,ean,descricao,preco_compra</code>
        </div>

        <form method="POST" action="{{ route('produtos.importar.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo (.csv ou .txt)</label>
                <input type="file" name="arquivo_produtos" class="block w-full" required>
            </div>

            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Importar
            </button>
        </form>
    </div>
</x-app-layout>
