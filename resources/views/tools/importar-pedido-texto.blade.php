{{-- resources/views/tools/importar-pedido-texto.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Importar Pedido via Texto (WhatsApp)
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6 space-y-4">

            {{-- Mensagens de erro --}}
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

            @if (session('error'))
                <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Explicação --}}
            <div class="border border-blue-100 rounded-md p-3 bg-blue-50 text-sm text-gray-700">
                <p class="font-semibold text-blue-800 mb-1">
                    Como funciona:
                </p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Cole abaixo o texto do pedido enviado pelo cliente via WhatsApp.</li>
                    <li>O sistema vai identificar as linhas com <strong>Quantidade</strong>, <strong>Código</strong> e <strong>Preço</strong>.</li>
                    <li>Será gerado um arquivo <strong>CSV</strong> no formato:
                        <code class="bg-white px-1 py-0.5 rounded border text-xs">
                            CODIGO;QUANTIDADE;PRECO_COMPRA;PONTOS;PRECO_REVENDA
                        </code>
                    </li>
                    <li>Depois, você pode usar esse CSV na tela de <strong>Importar Itens</strong> do Pedido de Compra.</li>
                </ul>
            </div>

            {{-- Exemplo colapsável --}}
            <details class="border border-gray-200 rounded-md p-3 text-sm text-gray-700">
                <summary class="cursor-pointer font-semibold text-gray-800">
                    Ver exemplo de texto de pedido
                </summary>
                <pre class="mt-2 bg-gray-50 p-3 rounded text-xs whitespace-pre-wrap">
Olá! Estou te enviando o meu pedido do Ciclo 18:

1 Unidade(s) - Código: 6587 - Preço: R$ 25,90 - Batom color tint FPS 8
1 Unidade(s) - Código: 6588 - Preço: R$ 25,90 - Batom color tint FPS 8
1 Unidade(s) - Código: 17042 - Preço: R$ 59,90 - Caneta delineadora de sobrancelhas

Total = R$ 2.464,50
                </pre>
            </details>

            {{-- Formulário --}}
            <form action="{{ route('tools.importar_pedido_texto.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Texto do pedido (copiado do WhatsApp)
                    </label>
                    <textarea
                        name="texto"
                        rows="15"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm font-mono"
                        placeholder="Cole aqui a mensagem completa do pedido..."
                    >{{ old('texto') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Dica: selecione a mensagem inteira no WhatsApp Web, copie e cole aqui.
                    </p>
                </div>

                <div class="flex justify-between items-center">
                    <div class="text-xs text-gray-500">
                        O CSV gerado será compatível com a importação de itens do Pedido de Compra.
                    </div>

                    <div class="space-x-2">
                        <a href="{{ url()->previous() }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">
                            Voltar
                        </a>

                        <button type="submit"
                                class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">
                            Gerar arquivo CSV
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
