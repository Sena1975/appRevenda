
{{-- resources/views/campanhas/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 sm:py-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $campanha->nome }}
            </h1>
            <p class="text-sm text-gray-500">
                Visualização da campanha de vendas.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('campanhas.index') }}"
               class="inline-flex items-center px-3 py-2 rounded border text-sm text-gray-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7" />
                </svg>
                Voltar
            </a>

            <a href="{{ route('campanhas.edit', $campanha->id) }}"
               class="inline-flex items-center px-3 py-2 rounded bg-emerald-600 text-white text-sm hover:bg-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 0 0 .707-.293l9.439-9.439a1 1 0 0 0 0-1.414l-3.586-3.586a1 1 0 0 0-1.414 0L4 14.586V20z" />
                </svg>
                Editar
            </a>

            <a href="{{ route('campanhas.restricoes', $campanha->id) }}"
               class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 10h16M4 14h16" />
                </svg>
                Restrições
            </a>
        </div>
    </div>

    {{-- Descrição --}}
    @if ($campanha->descricao)
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-1">
                Descrição
            </h2>
            <p class="text-sm text-gray-700">
                {{ $campanha->descricao }}
            </p>
        </div>
    @endif

    {{-- Dados principais --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Informações Gerais
            </h3>
            <dl class="text-sm text-gray-700 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo:</dt>
                    <dd class="font-medium">
                        {{ $campanha->tipo->descricao ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Período:</dt>
                    <dd class="font-medium">
                        {{ optional($campanha->data_inicio)->format('d/m/Y') }}
                        &rarr;
                        {{ optional($campanha->data_fim)->format('d/m/Y') }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Prioridade:</dt>
                    <dd class="font-medium">
                        {{ $campanha->prioridade }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Método PHP:</dt>
                    <dd class="font-mono text-xs">
                        {{ $campanha->metodo_php ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Status e Comportamento
            </h3>
            <dl class="text-sm text-gray-700 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Situação:</dt>
                    <dd>
                        @if($campanha->ativa)
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                Ativa
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                Inativa
                            </span>
                        @endif
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Cumulativa:</dt>
                    <dd>
                        @if($campanha->cumulativa)
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                Sim
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                Não
                            </span>
                        @endif
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Aplicação:</dt>
                    <dd>
                        @if($campanha->aplicacao_automatica)
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                Automática
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                                Manual
                            </span>
                        @endif
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Percentual desc. (perc_desc):</dt>
                    <dd class="font-medium">
                        {{ $campanha->perc_desc ? number_format($campanha->perc_desc, 2, ',', '.') . ' %' : '—' }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Valor base cupom:</dt>
                    <dd class="font-medium">
                        {{ $campanha->valor_base_cupom ? 'R$ ' . number_format($campanha->valor_base_cupom, 2, ',', '.') : '—' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Acumulação / Brinde (se aplicável) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Regras de Acumulação
            </h3>
            <dl class="text-sm text-gray-700 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Acumulação por valor:</dt>
                    <dd>
                        {{ $campanha->acumulativa_por_valor ? 'Sim' : 'Não' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Acumulação por quantidade:</dt>
                    <dd>
                        {{ $campanha->acumulativa_por_quantidade ? 'Sim' : 'Não' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo de acumulação:</dt>
                    <dd>
                        {{ $campanha->tipo_acumulacao ? ucfirst($campanha->tipo_acumulacao) : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Qtde mínima cupom:</dt>
                    <dd>
                        {{ $campanha->quantidade_minima_cupom ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Brinde
            </h3>
            <p class="text-sm text-gray-700">
                @if($campanha->produto_brinde_id && $campanha->produtoBrinde)
                    Produto brinde:
                    <span class="font-medium">
                        {{ $campanha->produtoBrinde->nome }}
                        @if($campanha->produtoBrinde->codfabnumero)
                            ({{ $campanha->produtoBrinde->codfabnumero }})
                        @endif
                    </span>
                @else
                    Nenhum produto de brinde configurado.
                @endif
            </p>
        </div>
    </div>

    {{-- Mensagens configuradas para a campanha --}}
    <div class="bg-white border rounded-lg p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">
            Mensagens de WhatsApp configuradas
        </h3>

        @if ($campanha->mensagensConfiguradas->isEmpty())
            <p class="text-sm text-gray-500">
                Nenhuma mensagem configurada para esta campanha ainda.
                @if (Route::has('campanhas.edit'))
                    <a href="{{ route('campanhas.edit', $campanha->id) }}"
                       class="text-blue-600 hover:underline">
                        Configurar agora
                    </a>.
                @endif
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-3 py-2">Evento</th>
                            <th class="px-3 py-2">Modelo de Mensagem</th>
                            <th class="px-3 py-2">Canal</th>
                            <th class="px-3 py-2">Ativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campanha->mensagensConfiguradas as $cfg)
                            @php
                                $labelEvento = $eventosIndicacao[$cfg->evento] ?? $cfg->evento;
                                $modelo      = $cfg->meodelo;
                            @endphp
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800">
                                        {{ $labelEvento }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cfg->evento }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    @if ($modelo)
                                        <div class="font-medium text-gray-800">
                                            {{ $modelo->nome }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Código: {{ $modelo->codigo }}
                                        </div>
                                    @else
                                        <span class="text-xs text-red-600">
                                            Modelo não encontrado
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                        {{ $modelo->canal ?? 'whatsapp' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    @if ($cfg->ativo ?? true)
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                            Ativo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                            Inativo
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
