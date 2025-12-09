@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
                {{-- Cabeçalho --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
                            Detalhes da Mensagem #{{ $mensagem->id }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">
                            Registro criado em
                            {{ optional($mensagem->created_at)->format('d/m/Y H:i') ?? '-' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        {{-- Status --}}
                        @php
                            $status = $mensagem->status ?? 'N/A';
                            $badgeClass = match ($status) {
                                'sent' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'queued' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            Status: {{ strtoupper($status) }}
                        </span>

                        {{-- Canal --}}
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                            Canal: {{ strtoupper($mensagem->canal ?? '-') }}
                        </span>

                        {{-- Direção --}}
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700">
                            Direção: {{ strtoupper($mensagem->direcao ?? '-') }}
                        </span>
                    </div>
                </div>

                {{-- Ações topo --}}
                <div class="mb-4 flex flex-col sm:flex-row gap-2">
                    <a href="{{ url()->previous() }}" title="Voltar para a tela anterior"
                        class="inline-flex items-center justify-center px-3 py-2 border border-gray-300
                          text-xs sm:text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white
                          hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                          focus:ring-indigo-500">
                        ← Voltar
                    </a>
                </div>

                {{-- GRID PRINCIPAL --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    {{-- Coluna 1 --}}
                    <div class="space-y-3">
                        {{-- Cliente --}}
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Cliente
                            </span>

                            @if ($mensagem->cliente)
                                @if (Route::has('clientes.show'))
                                    <a href="{{ route('clientes.show', $mensagem->cliente) }}"
                                        title="Ver detalhes do cliente"
                                        class="block text-sm text-indigo-700 hover:underline">
                                        {{ $mensagem->cliente->nome }}
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-800">
                                        {{ $mensagem->cliente->nome }}
                                    </span>
                                @endif
                            @else
                                <span class="block text-sm text-gray-800">-</span>
                            @endif
                        </div>

                        {{-- Pedido --}}
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Pedido
                            </span>

                            @if ($mensagem->pedido_id)
                                @if (Route::has('vendas.show'))
                                    <a href="{{ route('vendas.show', $mensagem->pedido_id) }}"
                                        title="Ver detalhes do pedido de venda"
                                        class="block text-sm text-indigo-700 hover:underline">
                                        #{{ $mensagem->pedido_id }}
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-800">
                                        #{{ $mensagem->pedido_id }}
                                    </span>
                                @endif
                            @else
                                <span class="block text-sm text-gray-800">-</span>
                            @endif
                        </div>

                        {{-- Campanha --}}
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Campanha
                            </span>

                            @if ($mensagem->campanha)
                                @if (Route::has('campanhas.show'))
                                    <a href="{{ route('campanhas.show', $mensagem->campanha) }}
                                   "title="Ver detalhes da campanha"
                                        class="block text-sm text-indigo-700 hover:underline">
                                        {{ $mensagem->campanha->nome }}
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-800">
                                        {{ $mensagem->campanha->nome }}
                                    </span>
                                @endif
                            @else
                                <span class="block text-sm text-gray-800">-</span>
                            @endif
                        </div>
                    </div>

                    {{-- Coluna 2 --}}
                    <div class="space-y-3">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Tipo
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $mensagem->tipo ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Provider
                            </span>
                            <span class="block text-sm text-gray-800">
                                {{ $mensagem->provider ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Provider Subscriber ID
                            </span>
                            <span class="block text-xs sm:text-sm text-gray-800 break-all">
                                {{ $mensagem->provider_subscriber_id ?? '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- Coluna 3 --}}
                    <div class="space-y-3">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Provider Message ID
                            </span>
                            <span class="block text-xs sm:text-sm text-gray-800 break-all">
                                {{ $mensagem->provider_message_id ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Provider Status (bruto)
                            </span>
                            <span class="block text-xs sm:text-sm text-gray-800 break-all">
                                {{ $mensagem->provider_status ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Datas / Timeline --}}
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">
                        Linha do tempo
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs sm:text-sm">
                        <div class="bg-gray-50 border border-gray-100 rounded-md p-3">
                            <span class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
                                Criada em
                            </span>
                            <span class="block text-gray-800 mt-1">
                                {{ optional($mensagem->created_at)->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 rounded-md p-3">
                            <span class="block text-[11px] font-semibold text-indigo-600 uppercase tracking-wide">
                                Enviada em (sent_at)
                            </span>
                            <span class="block text-gray-800 mt-1">
                                {{ optional($mensagem->sent_at)->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        </div>

                        <div class="bg-green-50 border border-green-100 rounded-md p-3">
                            <span class="block text-[11px] font-semibold text-green-600 uppercase tracking-wide">
                                Entregue em (delivered_at)
                            </span>
                            <span class="block text-gray-800 mt-1">
                                {{ optional($mensagem->delivered_at)->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        </div>

                        {{-- Card de falha só se existir failed_at --}}
                        @if ($mensagem->failed_at)
                            <div class="bg-red-50 border border-red-100 rounded-md p-3 md:col-span-3">
                                <span class="block text-[11px] font-semibold text-red-600 uppercase tracking-wide">
                                    Falha no envio
                                </span>
                                <span class="block text-gray-800 mt-1">
                                    {{ $mensagem->failed_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Conteúdo da mensagem --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-sm font-semibold text-gray-700">
                            Conteúdo da mensagem
                        </h2>

                        <button type="button" title="Copiar o texto da mensagem para a área de transferência"
                            onclick="navigator.clipboard.writeText(@js($mensagem->conteudo));"
                            class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium
                                   rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50
                                   focus:outline-none focus:ring-2 focus:ring-offset-2
                                   focus:ring-indigo-500">
                            Copiar texto
                        </button>
                    </div>

                    <div class="border border-gray-200 rounded-md bg-gray-50">
                        <textarea
                            class="w-full p-3 text-xs sm:text-sm bg-gray-50 border-0 rounded-md
                               focus:ring-0 focus:border-0"
                            rows="6" readonly>{{ $mensagem->conteudo }}</textarea>
                    </div>
                </div>

                {{-- Payload bruto (JSON) --}}
                <div>
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">
                        Payload (dados brutos do provider)
                    </h2>

                    @if (!empty($mensagem->payload))
                        <details class="group border border-gray-200 rounded-md bg-gray-50">
                            <summary class="flex items-center justify-between px-3 py-2 cursor-pointer select-none">
                                <span class="text-xs sm:text-sm text-gray-700">
                                    Clique para expandir/ocultar payload
                                </span>
                                <span class="text-gray-400 group-open:rotate-180 transform transition-transform">
                                    ▼
                                </span>
                            </summary>
                            <div class="border-t border-gray-200 px-3 py-3">
                                <pre class="text-[11px] sm:text-xs text-gray-800 overflow-x-auto whitespace-pre">
                                {{ json_encode($mensagem->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                            </pre>
                            </div>
                        </details>
                    @else
                        <p class="text-xs sm:text-sm text-gray-500 italic">
                            Nenhum payload armazenado para esta mensagem.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
