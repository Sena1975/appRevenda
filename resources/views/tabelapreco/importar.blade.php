{{-- resources/views/tabelapreco/importar.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Importar Tabela de Preços</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-xl mx-auto">

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tabelapreco.processImport') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Arquivo CSV do fornecedor
                </label>
                <input type="file" name="arquivo" accept=".csv,.txt" required
                       class="w-full border-gray-300 rounded-md text-sm">
                <p class="text-xs text-gray-500 mt-1">
                    Formato esperado: "Cód. Fábrica","Preço Compra","Preço Revenda","Pontuação","Data Início","codnotafiscal","ean","Data Fim","Ciclo"
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('tabelapreco.index') }}"
                   class="px-4 py-2 rounded border text-sm hover:bg-gray-50">
                    Voltar
                </a>
                <button type="submit"
                        class="px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                    Importar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
