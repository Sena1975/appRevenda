{{-- resources/views/whatsapp/config.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Configuração de WhatsApp / BotConversa
        </h1>

        @if(isset($empresa))
            <p class="text-sm text-gray-500 mb-4">
                Empresa ativa: <span class="font-semibold">{{ $empresa->nome ?? ('ID ' . $empresa->id) }}</span>
            </p>
        @endif

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('whatsapp.config.update') }}">
            @csrf

            {{-- Provider (apenas visual) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Provedor
                </label>
                <input type="text" value="BotConversa" disabled
                       class="w-full border rounded px-3 py-2 text-sm bg-gray-100 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">
                    No momento, apenas o provedor <strong>BotConversa</strong> está configurado.
                </p>
            </div>

            {{-- API URL --}}
            <div class="mb-4">
                <label for="api_url" class="block text-sm font-medium text-gray-700 mb-1">
                    URL da API
                </label>
                <input type="text"
                       name="api_url"
                       id="api_url"
                       value="{{ old('api_url', $config->api_url ?? '') }}"
                       class="w-full border rounded px-3 py-2 text-sm"
                       placeholder="Ex.: https://api.botconversa.com.br/v1/...">
                <p class="text-xs text-gray-500 mt-1">
                    Endereço base da API do BotConversa, se aplicável.
                </p>
            </div>

            {{-- API Token --}}
            <div class="mb-4">
                <label for="api_token" class="block text-sm font-medium text-gray-700 mb-1">
                    API Token / Chave
                </label>
                <textarea
                    name="api_token"
                    id="api_token"
                    rows="3"
                    class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Cole aqui o token de acesso à API do BotConversa">{{ old('api_token', $config->api_token ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Token de autenticação gerado no painel do BotConversa.
                </p>
            </div>

            {{-- ORIGIN TAG ID --}}
            <div class="mb-4">
                <label for="origin_tag_id" class="block text-sm font-medium text-gray-700 mb-1">
                    ID da TAG de Origem (BotConversa)
                </label>
                <input type="text"
                       name="origin_tag_id"
                       id="origin_tag_id"
                       value="{{ old('origin_tag_id', $config->origin_tag_id ?? '') }}"
                       class="w-full border rounded px-3 py-2 text-sm"
                       placeholder="Ex.: 123456">
                <p class="text-xs text-gray-500 mt-1">
                    Informe aqui o <strong>ID</strong> da TAG criada no painel do BotConversa (ex.: “Origem: App Revenda”).
                    Esta tag será aplicada automaticamente aos contatos criados/sincronizados pelo sistema.
                    Se não quiser usar tags agora, pode deixar em branco.
                </p>
            </div>

            {{-- Ativo / Padrão --}}
            <div class="mb-4 flex flex-col sm:flex-row gap-4">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="ativo" value="1"
                           class="rounded border-gray-300"
                           @checked(old('ativo', $config->ativo ?? false))>
                    <span class="ml-2">Configuração ativa</span>
                </label>

                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="is_default" value="1"
                           class="rounded border-gray-300"
                           @checked(old('is_default', $config->is_default ?? true))>
                    <span class="ml-2">Usar como configuração padrão</span>
                </label>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700 shadow-sm">
                    Salvar configurações
                </button>

                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 border text-sm rounded text-gray-700 hover:bg-gray-50">
                    Voltar ao painel
                </a>
            </div>
        </form>
    </div>
@endsection
