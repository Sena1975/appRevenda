{{-- resources/views/campanhas/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Campanhas</h1>
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
                Nova Campanha
            </a>
        </div>
    </div>

    {{-- Mensagens de sucesso --}}
    @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Se no futuro tiver erros globais --}}
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
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-3 py-2">#</th>
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
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2 text-xs text-gray-500">
                            {{ $campanha->id }}
                        </td>

                        <td class="px-3 py-2">
                            <div class="font-semibold text-gray-800">
                                {{ $campanha->nome }}
                            </div>
                            @if($campanha->descricao)
                                <div class="text-xs text-gray-500 truncate max-w-xs">
                                    {{ $campanha->descricao }}
                                </div>
                            @endif
                        </td>

                        <td class="px-3 py-2">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                {{ $campanha->tipo->descricao ?? '—' }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-sm text-gray-700">
                            @php
                                $ini = optional($campanha->data_inicio)->format('d/m/Y');
                                $fim = optional($campanha->data_fim)->format('d/m/Y');
                            @endphp
                            {{ $ini }} &rarr; {{ $fim }}
                        </td>

                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center justify-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                {{ $campanha->prioridade }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-center">
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

                        <td class="px-3 py-2 text-center">
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

                        <td class="px-3 py-2 text-center">
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

                        <td class="px-3 py-2 text-center">
                            <div class="inline-flex items-center gap-2">
                                {{-- Link para restrições --}}
                                <a href="{{ route('campanhas.restricoes', $campanha->id) }}"
                                   class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-50 text-blue-700 hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3" />
                                    </svg>
                                    Restrições
                                </a>

                                {{-- Se no futuro tiver tela de edição, já tá fácil de ligar
                                <a href="{{ route('campanhas.edit', $campanha->id) }}"
                                   class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    Editar
                                </a>
                                --}}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t">
                        <td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500">
                            Nenhuma campanha cadastrada ainda.
                            <a href="{{ route('campanhas.create') }}" class="text-blue-600 underline">Criar a primeira</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Paginação --}}
        @if ($campanhas instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="px-3 py-2 border-t bg-gray-50">
                {{ $campanhas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
