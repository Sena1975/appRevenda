{{-- resources/views/formapagamento/index.blade.php --}}
@extends('layouts.app')

@section('header')
    <h1 class="text-xl font-semibold text-gray-800">Formas de Pagamento</h1>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Gerencie as formas e regras de pagamento.</p>
        <a href="{{ route('formapagamento.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            + Nova Forma
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTROS (sem quebra; scroll-x) --}}
    <form method="GET" action="{{ route('formapagamento.index') }}" class="mb-4">
        <div class="overflow-x-auto">
            <div class="min-w-max flex flex-nowrap items-end gap-3 pr-2">
                <div class="flex flex-col shrink-0">
                    <label class="text-sm font-medium text-gray-700" for="busca">Busca</label>
                    <input id="busca" name="busca" value="{{ request('busca') }}"
                           class="h-10 w-72 border-gray-300 rounded-md shadow-sm"
                           placeholder="Nome da forma">
                </div>

                <div class="flex flex-col shrink-0">
                    <label class="text-sm font-medium text-gray-700" for="gera_receber">Gera Receber?</label>
                    <select id="gera_receber" name="gera_receber" class="h-10 w-40 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todas</option>
                        <option value="1" {{ request('gera_receber')==='1'?'selected':'' }}>Sim</option>
                        <option value="0" {{ request('gera_receber')==='0'?'selected':'' }}>Não</option>
                    </select>
                </div>

                <div class="flex flex-col shrink-0">
                    <label class="text-sm font-medium text-gray-700" for="ativo">Ativa?</label>
                    <select id="ativo" name="ativo" class="h-10 w-40 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todas</option>
                        <option value="1" {{ request('ativo')==='1'?'selected':'' }}>Ativa</option>
                        <option value="0" {{ request('ativo')==='0'?'selected':'' }}>Inativa</option>
                    </select>
                </div>

                <div class="flex flex-col shrink-0">
                    <label class="text-sm font-medium text-gray-700" for="por_pagina">Itens por página</label>
                    <select id="por_pagina" name="por_pagina"
                            class="h-10 w-36 border-gray-300 rounded-md shadow-sm"
                            onchange="this.form.submit()">
                        @foreach([10,25,50,100] as $n)
                            <option value="{{ $n }}" {{ (int)request('por_pagina',10)===$n?'selected':'' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 shrink-0">
                    <button class="h-10 rounded-md bg-blue-600 px-4 text-white hover:bg-blue-700 transition" type="submit">
                        Filtrar
                    </button>
                    <a href="{{ route('formapagamento.index') }}"
                       class="h-10 rounded-md border px-4 text-gray-700 hover:bg-gray-50 transition">
                        Limpar
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Nome</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Gera Receber</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Máx. Parcelas</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 w-28">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($formas as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $f->nome }}</td>
                        <td class="px-3 py-2">{{ $f->gera_receber ? 'Sim' : 'Não' }}</td>
                        <td class="px-3 py-2">{{ $f->max_parcelas }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-1 rounded text-white {{ $f->ativo ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $f->ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('formapagamento.edit', $f) }}"
                                   class="rounded border px-2 py-1 hover:bg-gray-50">Editar</a>
                                <form action="{{ route('formapagamento.destroy', $f) }}" method="POST"
                                      onsubmit="return confirm('Excluir esta forma?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded border px-2 py-1 text-red-600 hover:bg-red-50">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Nenhuma forma encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $formas->appends(request()->except('page'))->links() }}
    </div>
</div>
@endsection
