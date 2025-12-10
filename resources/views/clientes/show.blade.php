@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">

                @php
                    $idade = $cliente->data_nascimento ? $cliente->data_nascimento->age : null;
                @endphp

                {{-- Cabeçalho com foto e nome --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <img src="{{ $cliente->foto_url }}" alt="Foto do cliente"
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border border-gray-200">

                        <div>
                            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
                                {{ $cliente->nome ?? 'Cliente sem nome' }}
                            </h1>

                            <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                {{-- Status --}}
                                @if ($cliente->status)
                                    @php
                                        $statusClass = match (strtolower($cliente->status)) {
                                            'ativo' => 'bg-green-100 text-green-800',
                                            'inativo' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold {{ $statusClass }}">
                                        {{ strtoupper($cliente->status) }}
                                    </span>
                                @endif

                                {{-- Origem --}}
                                @if ($cliente->origem_cadastro)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">
                                        Origem: {{ $cliente->origem_cadastro }}
                                    </span>
                                @endif

                                {{-- Código interno / empresa --}}
                                @if ($cliente->codigo_empresa)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-50 text-gray-700">
                                        Código na empresa: {{ $cliente->codigo_empresa }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Ações topo --}}
                    <div class="flex flex-wrap gap-2 justify-start sm:justify-end">

                        <a href="{{ route('clientes.index') }}" title="Voltar para a lista de clientes"
                            class="inline-flex items-center px-3 py-2 border border-gray-300
                              text-xs sm:text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white
                              hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                              focus:ring-indigo-500">
                            ← Voltar
                        </a>

                        @if (Route::has('clientes.edit'))
                            <a href="{{ route('clientes.edit', $cliente) }}" title="Editar este cliente"
                                class="inline-flex items-center px-3 py-2 border border-indigo-600
                                  text-xs sm:text-sm font-medium rounded-md shadow-sm
                                  text-white bg-indigo-600 hover:bg-indigo-700
                                  focus:outline-none focus:ring-2 focus:ring-offset-2
                                  focus:ring-indigo-500">
                                Editar
                            </a>
                        @endif

                        @if ($cliente->whatsapp_link)
                            <a href="{{ $cliente->whatsapp_link }}" target="_blank"
                                title="Abrir conversa de WhatsApp com este cliente"
                                class="inline-flex items-center px-3 py-2 border border-green-600
                                  text-xs sm:text-sm font-medium rounded-md shadow-sm
                                  text-white bg-green-600 hover:bg-green-700
                                  focus:outline-none focus:ring-2 focus:ring-offset-2
                                  focus:ring-green-500">
                                WhatsApp
                            </a>
                        @endif

                        @if ($cliente->whatsapp_indicacao_link)
                            <a href="{{ $cliente->whatsapp_indicacao_link }}" target="_blank"
                                title="Enviar para o cliente o link de indicação dele"
                                class="inline-flex items-center px-3 py-2 border border-amber-500
                                  text-xs sm:text-sm font-medium rounded-md shadow-sm
                                  text-amber-900 bg-amber-100 hover:bg-amber-200
                                  focus:outline-none focus:ring-2 focus:ring-offset-2
                                  focus:ring-amber-500">
                                Link de Indicação
                            </a>
                        @endif
                    </div>
                </div>

                {{-- GRID PRINCIPAL: dados pessoais + contato + endereço --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                    {{-- Coluna: Dados pessoais --}}
                    <div class="space-y-3">
                        <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-1">
                            Dados pessoais
                        </h2>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                CPF
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->cpf ?? '-' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Data de nascimento
                                </span>
                                <span class="block text-sm text-gray-800">
                                    @if ($cliente->data_nascimento)
                                        {{ $cliente->data_nascimento->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Idade
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $idade ? $idade . ' anos' : '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Sexo
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->sexo ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Filhos
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->filhos ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Time do coração
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->timecoracao ?? '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- Coluna: Contato --}}
                    <div class="space-y-3">
                        <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-1">
                            Contato
                        </h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Telefone
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->telefone ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    WhatsApp
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->whatsapp ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                E-mail
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->email ?? '-' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Instagram
                                </span>
                                @if ($cliente->instagram)
                                    <a href="https://instagram.com/{{ $cliente->instagram }}" target="_blank"
                                        title="Abrir perfil no Instagram"
                                        class="block text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ '@' . $cliente->instagram }}
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-800">-</span>
                                @endif
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Facebook
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->facebook ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Telegram
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->telegram ? '@' . $cliente->telegram : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Endereço + Indicação --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

                    {{-- Endereço --}}
                    <div class="space-y-3">
                        <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-1">
                            Endereço
                        </h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    CEP
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->cep ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    UF
                                </span>
                                <span class="block text-sm text-gray-800">
                                    {{ $cliente->uf ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Cidade
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->cidade ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Bairro
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->bairro ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Endereço
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $cliente->endereco ?? '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- Indicação / BotConversa --}}
                    <div class="space-y-3">
                        <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-1">
                            Indicação e integrações
                        </h2>

                        {{-- Indicador --}}
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Indicador
                            </span>

                            @if ($cliente->indicador)
                                @if (Route::has('clientes.show'))
                                    <a href="{{ route('clientes.show', $cliente->indicador) }}"
                                        title="Ver detalhes do cliente indicador"
                                        class="block text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ $cliente->indicador->nome }} (ID: {{ $cliente->indicador->id }})
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-800">
                                        {{ $cliente->indicador->nome }} (ID: {{ $cliente->indicador->id }})
                                    </span>
                                @endif
                            @else
                                <span class="block text-sm text-gray-800">Nenhum indicador cadastrado</span>
                            @endif
                        </div>

                        {{-- BotConversa --}}
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                BotConversa Subscriber ID
                            </span>
                            <span class="block text-xs sm:text-sm text-gray-800 break-all">
                                {{ $cliente->botconversa_subscriber_id ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ÚLTIMAS MENSAGENS --}}
                <div class="mt-4">
                    <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-2 mb-3">
                        Últimas mensagens para este cliente
                    </h2>

                    {{-- FILTROS DAS MENSAGENS --}}
                    <form method="GET" action="{{ route('clientes.show', $cliente) }}"
                        class="mb-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                        {{-- Canal --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Canal
                            </label>
                            <select name="canal"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
                                <option value="">-- todos --</option>
                                @foreach (['whatsapp', 'sms', 'email'] as $can)
                                    <option value="{{ $can }}" @selected(($filtroCanal ?? '') === $can)>
                                        {{ strtoupper($can) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Tipo de mensagem
                            </label>
                            <select name="tipo"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
                                <option value="">-- todos --</option>
                                @foreach ($tiposMensagens as $tipo)
                                    <option value="{{ $tipo }}" @selected(($filtroTipo ?? '') === $tipo)>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botões --}}
                        <div class="flex gap-2">
                            <button type="submit"
                                class="inline-flex items-center justify-center px-3 py-2 border border-transparent
                           text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600
                           hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                           focus:ring-indigo-500 w-full md:w-auto">
                                Filtrar mensagens
                            </button>

                            <a href="{{ route('clientes.show', $cliente) }}"
                                class="inline-flex items-center justify-center px-3 py-2 border border-gray-300
                      text-xs sm:text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white
                      hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                      focus:ring-indigo-500 w-full md:w-auto">
                                Limpar
                            </a>
                        </div>
                    </form>

                    {{-- TABELA DE MENSAGENS --}}
                    @if (isset($mensagensRecentes) && $mensagensRecentes->count())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Data envio</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Tipo</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Campanha</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Canal</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">
                                            Preview</th>
                                        <th
                                            class="px-2 py-2 text-right font-medium text-gray-500 uppercase tracking-wider">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach ($mensagensRecentes as $msg)
                                        @php
                                            $dataEnvio = $msg->sent_at ?? $msg->created_at;
                                            $badgeClass = match ($msg->status) {
                                                'sent' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'queued' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                                {{ $dataEnvio ? $dataEnvio->format('d/m/Y H:i') : '-' }}
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                                {{ $msg->tipo ?? '-' }}
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                                {{ $msg->campanha?->nome ?? '-' }}
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                                    {{ strtoupper($msg->status ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                                {{ strtoupper($msg->canal ?? '-') }}
                                            </td>
                                            <td class="px-2 py-2 max-w-xs">
                                                <span class="block text-gray-700 text-xs sm:text-sm truncate"
                                                    title="{{ $msg->conteudo }}">
                                                    {{ \Illuminate\Support\Str::limit($msg->conteudo, 60) }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-right whitespace-nowrap">
                                                @if (Route::has('mensagens.show'))
                                                    <a href="{{ route('mensagens.show', $msg) }}"
                                                        title="Ver detalhes desta mensagem"
                                                        class="text-indigo-600 hover:text-indigo-800 hover:underline text-xs sm:text-sm">
                                                        Ver
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs sm:text-sm text-gray-500">
                            Nenhuma mensagem encontrada para este cliente (com os filtros atuais).
                        </p>
                    @endif
                </div>


            </div>
        </div>
    </div>
@endsection
