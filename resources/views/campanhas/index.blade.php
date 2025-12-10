{{-- resources/views/campanhas/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Campanhas de Vendas</h1>
            <p class="text-sm text-gray-500">
                Gerencie as campanhas ativas, datas, prioridades e restrições de participação.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('campanhas.create') }}"
               class="inline-flex items-center px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v16m8-8H4" />
                </svg>
                Novo Campanha
            </a>
        </div>
    </div>

    {{-- Mensagens de sucesso --}}
    @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Erros globais, se houver --}}
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
            <ul class="list-disc ml-4">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border rounded shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-3 py-2 w-12">#</th>
                        <th class="px-3 py-2">Nome</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Período</th>
                        <th class="px-3 py-2 text-center">Prioridade</th>
                        <th class="px-3 py-2 text-center">Ativa</th>
                        <th class="px-3 py-2 text-center">Cumulativa</th>
                        <th class="px-3 py-2 text-center">Aplicação</th>
                        <th class="px-3 py-2 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campanhas as $campanha)
                        @php
                            $ini = optional($campanha->data_inicio)->format('d/m/Y');
                            $fim = optional($campanha->data_fim)->format('d/m/Y');
                        @endphp

                        <tr class="border-t hover:bg-gray-50">
                            {{-- ID --}}
                            <td class="px-3 py-2 text-xs text-gray-500 align-top">
                                {{ $campanha->id }}
                            </td>

                            {{-- Nome + descrição --}}
                            <td class="px-3 py-2 align-top">
                                <div class="font-semibold text-gray-800">
                                    <a href="{{ route('campanhas.edit', ['campanha' => $campanha->id]) }}"
                                       class="text-blue-700 hover:underline">
                                        {{ $campanha->nome }}
                                    </a>
                                </div>
                                @if($campanha->descricao)
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ $campanha->descricao }}
                                    </div>
                                @endif
                            </td>

                            {{-- Tipo --}}
                            <td class="px-3 py-2 align-top">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                    {{ $campanha->tipo->descricao ?? '—' }}
                                </span>
                            </td>

                            {{-- Período --}}
                            <td class="px-3 py-2 text-sm text-gray-700 align-top whitespace-nowrap">
                                {{ $ini }} &rarr; {{ $fim }}
                            </td>

                            {{-- Prioridade --}}
                            <td class="px-3 py-2 text-center align-top">
                                <span class="inline-flex items-center justify-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                    {{ $campanha->prioridade }}
                                </span>
                            </td>

                            {{-- Ativa --}}
                            <td class="px-3 py-2 text-center align-top">
                                @if($campanha->ativa)
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                        Ativa
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                        Inativa
                                    </span>
                                @endif
                            </td>

                            {{-- Cumulativa --}}
                            <td class="px-3 py-2 text-center align-top">
                                @if($campanha->cumulativa)
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                        Sim
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                        Não
                                    </span>
                                @endif
                            </td>

                            {{-- Aplicação automática / manual --}}
                            <td class="px-3 py-2 text-center align-top">
                                @if($campanha->aplicacao_automatica)
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                        Automática
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                                        Manual
                                    </span>
                                @endif
                            </td>

                            {{-- Ações (padrão parecido com Pedidos de Venda) --}}
                            <td class="px-3 py-2 text-right align-top">
                                <div class="inline-flex items-center gap-1">

                                    {{-- Visualizar (se existir rota show) --}}
                                    @if (Route::has('campanhas.show'))
                                        <a href="{{ route('campanhas.show', $campanha->id) }}"
                                           class="p-1 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100"
                                           title="Visualizar campanha">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                 viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Editar --}}
                                    <a href="{{ route('campanhas.edit', $campanha->id) }}"
                                       class="p-1 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
                                       title="Editar campanha">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                             viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 0 0 .707-.293l9.439-9.439a1 1 0 0 0 0-1.414l-3.586-3.586a1 1 0 0 0-1.414 0L4 14.586V20z" />
                                        </svg>
                                    </a>

                                    {{-- Restrições / Produtos --}}
                                    <a href="{{ route('campanhas.restricoes', $campanha->id) }}"
                                       class="p-1 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100"
                                       title="Restrições da campanha">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                             viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 6h16M4 10h10M4 14h7M4 18h13" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t">
                            <td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500">
                                Nenhuma campanha cadastrada ainda.
                                <a href="{{ route('campanhas.create') }}" class="text-blue-600 underline">
                                    Criar a primeira
                                </a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if ($campanhas instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="px-3 py-2 border-t bg-gray-50">
                {{ $campanhas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
