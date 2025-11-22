{{-- resources/views/tabelapreco/index.blade.php --}}
@extends('layouts.app')

@section('header')
    <h1 class="text-xl font-semibold text-gray-800">Tabela de Preços</h1>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        {{-- Topo: Ações --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Gerencie as vigências e valores por produto.</p>
            <div class="flex gap-2">
                <a href="{{ route('tabelapreco.formImport') }}"
                    class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                    Importar CSV
                </a>
                <a href="{{ route('tabelapreco.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    + Novo Preço
                </a>
            </div>
        </div>


        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- FILTROS (sem quebra; scroll horizontal no mobile) --}}
        <form method="GET" action="{{ route('tabelapreco.index') }}" class="mb-4">
            <div class="overflow-x-auto">
                <div class="min-w-max flex flex-nowrap items-end gap-3 pr-2">
                    {{-- Produto --}}
                    <div class="flex flex-col shrink-0">
                        <label for="produto_id" class="text-sm font-medium text-gray-700">Produto</label>
                        <select name="produto_id" id="produto_id" class="h-10 w-80 border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            @foreach ($produtos as $p)
                                <option value="{{ $p->id }}"
                                    {{ (string) request('produto_id') === (string) $p->id ? 'selected' : '' }}>
                                    {{ $p->nome }} @if ($p->codfabnumero)
                                        - {{ $p->codfabnumero }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="flex flex-col shrink-0">
                        <label for="status" class="text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="h-10 w-40 border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Ativo</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    </div>

                    {{-- Vigência --}}
                    <div class="flex flex-col shrink-0">
                        <label for="vigencia" class="text-sm font-medium text-gray-700">Vigência</label>
                        <select name="vigencia" id="vigencia" class="h-10 w-44 border-gray-300 rounded-md shadow-sm">
                            <option value="">Todas</option>
                            <option value="atual" {{ request('vigencia') === 'atual' ? 'selected' : '' }}>Atual</option>
                            <option value="futura" {{ request('vigencia') === 'futura' ? 'selected' : '' }}>Futura</option>
                            <option value="expirada" {{ request('vigencia') === 'expirada' ? 'selected' : '' }}>Expirada</option>
                        </select>
                    </div>

                    {{-- Itens por página --}}
                    <div class="flex flex-col shrink-0">
                        <label for="por_pagina" class="text-sm font-medium text-gray-700">Itens por página</label>
                        <select name="por_pagina" id="por_pagina" class="h-10 w-36 border-gray-300 rounded-md shadow-sm"
                            onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}"
                                    {{ (int) request('por_pagina', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Busca livre --}}
                    <div class="flex flex-col shrink-0">
                        <label for="busca" class="text-sm font-medium text-gray-700">Busca</label>
                        <input type="text" id="busca" name="busca" value="{{ request('busca') }}"
                            placeholder="Nome, codfab, número..." class="h-10 w-64 border-gray-300 rounded-md shadow-sm">
                    </div>

                    {{-- Ações --}}
                    <div class="flex items-end gap-2 shrink-0">
                        <button type="submit"
                            class="h-10 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 text-white hover:bg-blue-700 transition">
                            Filtrar
                        </button>
                        <a href="{{ route('tabelapreco.index') }}"
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
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Cód. Fábrica</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Produto</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Preço Compra</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Preço Revenda</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Pontuação</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Início</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fim</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600 w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tabelas as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $item->codfab ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <div class="leading-tight">
                                    <div class="font-medium text-gray-800">{{ $item->produto->nome ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">
                                        @if (optional($item->produto)->codfab)
                                            codfab: {{ $item->produto->codfab }}
                                        @endif
                                        @if (optional($item->produto)->codfabnumero)
                                            • num: {{ $item->produto->codfabnumero }}
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">R$ {{ number_format($item->preco_compra ?? 0, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">R$
                                {{ number_format($item->preco_revenda, 2, ',', '.') }}</td>
                            <td class="px-3 py-2">{{ $item->pontuacao }}</td>
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($item->data_inicio)->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($item->data_fim)->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">
                                <span
                                    class="px-2 py-1 rounded text-white {{ $item->status ? 'bg-green-500' : 'bg-red-500' }}">
                                    {{ $item->status ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('tabelapreco.edit', $item->id) }}"
                                        class="rounded border px-2 py-1 hover:bg-gray-50">Editar</a>
                                    <form action="{{ route('tabelapreco.destroy', $item->id) }}" method="POST"
                                        onsubmit="return confirm('Excluir este registro?')">
                                        @csrf @method('DELETE')
                                        <button
                                            class="rounded border px-2 py-1 text-red-600 hover:bg-red-50">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-gray-500">Nenhum registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação (preserva filtros) --}}
        @if (method_exists($tabelas, 'links'))
            <div class="mt-4">
                {{ $tabelas->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>
@endsection
