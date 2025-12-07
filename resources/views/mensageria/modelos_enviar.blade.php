<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Enviar modelo: {{ $modelo->nome }}
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">

        <p class="text-sm text-gray-600 mb-4">
            <strong>Prévia do texto:</strong>
        </p>
        <pre class="bg-gray-50 border rounded p-3 text-sm whitespace-pre-wrap mb-6">
{{ $modelo->conteudo }}
        </pre>

        <form action="{{ route('mensageria.modelos.enviar', $modelo) }}" method="POST">
            @csrf

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

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Selecione os clientes que vão receber esta mensagem:
                </label>

                <div class="border rounded max-h-64 overflow-y-auto p-2">
                    @foreach ($clientes as $cliente)
                        <label class="flex items-center space-x-2 text-sm py-1">
                            <input type="checkbox"
                                   name="clientes[]"
                                   value="{{ $cliente->id }}"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span>
                                {{ $cliente->nome }}
                                @if ($cliente->telefone)
                                    <span class="text-xs text-gray-500">({{ $cliente->telefone }})</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    (Melhoria futura: filtros, busca, grupos, segmentação etc.)
                </p>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('mensageria.modelos.index') }}"
                   class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                    Voltar
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                    Enviar mensagem
                </button>
            </div>
        </form>

    </div>
</x-app-layout>
