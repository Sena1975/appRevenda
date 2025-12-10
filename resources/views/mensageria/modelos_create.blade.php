{{-- resources/views/mensageria/modelos_create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Novo Modelo de Mensagem
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">

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

        <form action="{{ route('mensageria.modelos.store') }}" method="POST">
            @csrf

            {{-- Nome --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nome do modelo
                </label>
                <input type="text"
                       name="nome"
                       value="{{ old('nome') }}"
                       class="w-full border-gray-300 rounded shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ex.: Boas-vindas para novo cliente">
                <p class="text-xs text-gray-500 mt-1">
                    Nome amigável apenas para identificação interna.
                </p>
            </div>

            {{-- Código --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Código
                </label>
                <input type="text"
                       name="codigo"
                       value="{{ old('codigo') }}"
                       class="w-full border-gray-300 rounded shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ex.: boas_vindas_cliente">
                <p class="text-xs text-gray-500 mt-1">
                    Usado pelo sistema (único). Ex.: <code>boas_vindas_cliente</code>, <code>convite_indicacao_primeira_compra</code>.
                </p>
            </div>

            {{-- Canal --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Canal
                </label>
                <select name="canal"
                        class="w-full border-gray-300 rounded shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="whatsapp" {{ old('canal', 'whatsapp') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    {{-- Futuro: SMS, E-mail, etc. --}}
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    No momento apenas WhatsApp está disponível.
                </p>
            </div>

            {{-- Conteúdo --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Conteúdo da mensagem
                </label>
                <textarea name="conteudo"
                          rows="10"
                          class="w-full border-gray-300 rounded shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Digite aqui o texto da mensagem que será enviada no WhatsApp...">{{ old('conteudo') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Você pode usar placeholders como:
                    <code>{{ '{' }}{{ 'NOME_CLIENTE' }}{{ '}' }}</code>,
                    <code>{{ '{' }}{{ 'NUMERO_PEDIDO' }}{{ '}' }}</code>,
                    <code>{{ '{' }}{{ 'VALOR_PEDIDO' }}{{ '}' }}</code>, etc.
                    (a substituição é feita pela lógica do sistema).
                </p>
            </div>

            {{-- Ativo --}}
            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox"
                           name="ativo"
                           value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           {{ old('ativo', true) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-700">
                        Modelo ativo
                    </span>
                </label>
                <p class="text-xs text-gray-500 mt-1">
                    Se desmarcado, o modelo ficará desativado e não aparecerá para envio.
                </p>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('mensageria.modelos.index') }}"
                   class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                    Voltar
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                    Salvar modelo
                </button>
            </div>
        </form>

    </div>
</x-app-layout>
