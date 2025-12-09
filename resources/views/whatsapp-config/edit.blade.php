{{-- resources/views/whatsapp-config/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar configuração de WhatsApp
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4">
                <a href="{{ route('whatsapp-config.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-800">
                    ← Voltar para lista
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <div class="font-semibold mb-1">Ops! Verifique os campos abaixo:</div>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('whatsapp-config.update', $config) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Provider --}}
                        <div>
                            <label for="provider" class="block text-sm font-medium text-gray-700">
                                Provedor *
                            </label>
                            <select id="provider" name="provider"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @php
                                    $provOld = old('provider', $config->provider);
                                @endphp
                                <option value="">Selecione...</option>
                                <option value="botconversa" {{ $provOld === 'botconversa' ? 'selected' : '' }}>
                                    BotConversa
                                </option>
                                <option value="zapi" {{ $provOld === 'zapi' ? 'selected' : '' }}>
                                    Z-API
                                </option>
                                <option value="other" {{ $provOld === 'other' ? 'selected' : '' }}>
                                    Outro / Custom
                                </option>
                            </select>
                        </div>

                        {{-- Nome exibicao --}}
                        <div>
                            <label for="nome_exibicao" class="block text-sm font-medium text-gray-700">
                                Nome para exibição
                            </label>
                            <input type="text" id="nome_exibicao" name="nome_exibicao"
                                   value="{{ old('nome_exibicao', $config->nome_exibicao) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="Ex.: WhatsApp Vendas, WhatsApp Cobrança">
                        </div>

                        {{-- Número --}}
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700">
                                Número do WhatsApp
                            </label>
                            <input type="text" id="phone_number" name="phone_number"
                                   value="{{ old('phone_number', $config->phone_number) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="Ex.: 5571999999999">
                            <p class="mt-1 text-xs text-gray-500">
                                Informe com DDI+DDD (ex.: 55 + DDD + número).
                            </p>
                        </div>

                        {{-- Ativo / Padrão --}}
                        <div class="flex flex-col justify-center space-y-2 mt-2 md:mt-0">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="ativo" value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       {{ old('ativo', $config->ativo ? 1 : 0) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Conexão ativa</span>
                            </label>

                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_default" value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       {{ old('is_default', $config->is_default ? 1 : 0) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Conexão padrão da empresa</span>
                            </label>

                            <p class="mt-1 text-xs text-gray-500">
                                Apenas uma conexão pode ser marcada como padrão. Ao salvar, as demais
                                serão desmarcadas.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">
                            Credenciais e detalhes da API
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">
                            Atualize as credenciais conforme o provedor escolhido.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="api_url" class="block text-sm font-medium text-gray-700">
                                    API URL / Endpoint
                                </label>
                                <input type="text" id="api_url" name="api_url"
                                       value="{{ old('api_url', $config->api_url) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label for="api_key" class="block text-sm font-medium text-gray-700">
                                    API Key (se aplicável)
                                </label>
                                <input type="text" id="api_key" name="api_key"
                                       value="{{ old('api_key', $config->api_key) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label for="token" class="block text-sm font-medium text-gray-700">
                                    Token / Access Token
                                </label>
                                <input type="text" id="token" name="token"
                                       value="{{ old('token', $config->token) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label for="instance_id" class="block text-sm font-medium text-gray-700">
                                    ID da instância (se aplicável)
                                </label>
                                <input type="text" id="instance_id" name="instance_id"
                                       value="{{ old('instance_id', $config->instance_id) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            {{-- ID da TAG de Origem (BotConversa) --}}
                            <div class="md:col-span-2">
                                <label for="origin_tag_id" class="block text-sm font-medium text-gray-700">
                                    ID da TAG de Origem (BotConversa)
                                </label>
                                <input type="text" id="origin_tag_id" name="origin_tag_id"
                                       value="{{ old('origin_tag_id', $config->origin_tag_id) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                       placeholder="Ex.: 123456">
                                <p class="mt-1 text-xs text-gray-500">
                                    Informe o <strong>ID</strong> da TAG criada no painel do BotConversa
                                    (ex.: “Origem: App Revenda”). Se não quiser usar TAG agora, pode deixar em branco.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <div>
                            <p class="text-xs text-gray-500">
                                Criado em: {{ $config->created_at?->format('d/m/Y H:i') }}<br>
                                Atualizado em: {{ $config->updated_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <a href="{{ route('whatsapp-config.index') }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest text-gray-700 bg-white hover:bg-gray-50">
                                Cancelar
                            </a>

                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Salvar alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
