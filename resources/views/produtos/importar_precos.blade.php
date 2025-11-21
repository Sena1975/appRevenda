<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Importar Preços de Produtos (Fornecedor)
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">

        {{-- Mensagens de sucesso --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Erros --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                <strong>Ops! Verifique os erros abaixo:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Link para TXT de códigos não encontrados --}}
        @if (session('arquivo_nao_encontrados'))
            @php
                $arquivo = session('arquivo_nao_encontrados');
            @endphp
            <div class="mb-4 p-3 rounded bg-yellow-100 text-yellow-800 text-sm">
                Alguns códigos não foram encontrados na base de produtos.
                <br>
                <a href="{{ route('produtos.download_nao_encontrados', ['arquivo' => $arquivo]) }}"
                   class="underline font-semibold">
                    Clique aqui para baixar a lista.
                </a>
            </div>
        @endif

        <form action="{{ route('produtos.importar_precos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Arquivo de preços (CSV/TXT)
                </label>
                <input type="file" name="arquivo_precos"
                       class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>

                <p class="mt-2 text-xs text-gray-500">
                    Formato esperado (separado por ponto e vírgula ou vírgula):
                    <br>
                    <code>codigo;preco_compra;preco_revenda;pontuacao</code>
                    <br>
                    Exemplo: <code>6587;18,13;25,90;2</code>
                </p>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('produtos.index') }}"
                   class="px-4 py-2 bg-gray-300 text-gray-800 rounded shadow text-sm">
                    Voltar
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded shadow text-sm">
                    Importar e Atualizar Preços
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
