{{-- resources/views/fornecedores/index.blade.php --}}
@extends('layouts.app')

@section('header')
    <h1 class="text-xl font-semibold text-gray-800">Fornecedores</h1>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
    {{-- Topo: ação --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Gerencie seus fornecedores e contatos.</p>
        <a href="{{ route('fornecedores.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            + Novo Fornecedor
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTROS (sem quebra; scroll horizontal no mobile) --}}
    <form method="GET" action="{{ route('fornecedores.index') }}" class="mb-4">
        <div class="overflow-x-auto">
            <div class="min-w-max flex flex-nowrap items-end gap-3 pr-2">
                {{-- Busca livre (razão, fantasia, cnpj) --}}
                <div class="flex flex-col shrink-0">
                    <label for="busca" class="text-sm font-medium text-gray-700">Busca</label>
                    <input type="text" id="busca" name="busca"
                           value="{{ request('busca') }}"
                           placeholder="Razão, fantasia ou CNPJ"
                           class="h-10 w-72 border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Status --}}
                <div class="flex flex-col shrink-0">
                    <label for="status" class="text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="h-10 w-40 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        <option value="1" {{ request('status')==='1'?'selected':'' }}>Ativo</option>
                        <option value="0" {{ request('status')==='0'?'selected':'' }}>Inativo</option>
                    </select>
                </div>

                {{-- Itens por página --}}
                <div class="flex flex-col shrink-0">
                    <label for="por_pagina" class="text-sm font-medium text-gray-700">Itens por página</label>
                    <select name="por_pagina" id="por_pagina"
                            class="h-10 w-36 border-gray-300 rounded-md shadow-sm"
                            onchange="this.form.submit()">
                        @foreach([10,25,50,100] as $n)
                            <option value="{{ $n }}" {{ (int)request('por_pagina',10)===$n?'selected':'' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Ações --}}
                <div class="flex items-end gap-2 shrink-0">
                    <button type="submit"
                            class="h-10 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 text-white hover:bg-blue-700 transition">
                        Filtrar
                    </button>
                    <a href="{{ route('fornecedores.index') }}"
                       class="h-10 inline-flex items-center gap-2 rounded-md border px-4 text-gray-700 hover:bg-gray-50 transition">
                        Limpar
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Tabela --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Razão Social</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Nome Fantasia</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">CNPJ</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Contato</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Telefone / Whats</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Redes</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">E-mail</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Endereço</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 w-28">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($fornecedores as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $f->razaosocial }}</td>
                        <td class="px-3 py-2">{{ $f->nomefantasia ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $f->cnpj }}</td>
                        <td class="px-3 py-2">{{ $f->pessoacontato ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <div class="text-xs leading-tight">
                                <div>{{ $f->telefone ?? '—' }}</div>
                                <div>{{ $f->whatsapp ? 'Whats: '.$f->whatsapp : '' }}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            @if($f->instagram) <div>IG: {{ $f->instagram }}</div> @endif
                            @if($f->facebook) <div>FB: {{ $f->facebook }}</div> @endif
                            @if($f->telegram) <div>TG: {{ $f->telegram }}</div> @endif
                            @if(!$f->instagram && !$f->facebook && !$f->telegram) — @endif
                        </td>
                        <td class="px-3 py-2">{{ $f->email ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $f->endereco ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-1 rounded text-white {{ $f->status ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $f->status ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('fornecedores.edit', $f) }}"
                                   class="rounded border px-2 py-1 hover:bg-gray-50">Editar</a>
                                <form action="{{ route('fornecedores.destroy', $f) }}" method="POST"
                                      onsubmit="return confirm('Excluir este fornecedor?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded border px-2 py-1 text-red-600 hover:bg-red-50">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-3 py-6 text-center text-gray-500">Nenhum fornecedor encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação (preserva filtros) --}}
    <div class="mt-4">
        {{ $fornecedores->appends(request()->except('page'))->links() }}
    </div>
</div>
@endsection
