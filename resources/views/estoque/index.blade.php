{{-- resources/views/estoque/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-800">Estoque Consolidado</h1>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtro --}}
    <form method="GET" action="{{ route('estoque.index') }}" class="mb-4">
        <div class="flex gap-2">
            <input
                type="text"
                name="busca"
                value="{{ request('busca') }}"
                placeholder="Buscar por nome ou código do produto..."
                class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm"
            >
            <button
                type="submit"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Buscar
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left">ID</th>
                    <th class="px-3 py-2 text-left">Produto</th>
                    <th class="px-3 py-2 text-right">Estoque Gerencial</th>
                    <th class="px-3 py-2 text-right">Reservado</th>
                    <th class="px-3 py-2 text-right">Avaria</th>
                    <th class="px-3 py-2 text-right">Disponível</th>
                    <th class="px-3 py-2 text-right">Últ. Preço Compra</th>
                    <th class="px-3 py-2 text-right">Últ. Preço Venda</th>
                    <th class="px-3 py-2 text-center">Última Mov.</th>
                    <th class="px-3 py-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($estoques as $estoque)
                    <tr class="border-t">
                        <td class="px-3 py-2 align-top">
                            {{ $estoque->id }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900">
                                {{ $estoque->produto->nome ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Cód. fábrica: {{ $estoque->codfabnumero ?? '-' }}
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ number_format($estoque->estoque_gerencial, 3, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ number_format($estoque->reservado, 3, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ number_format($estoque->avaria, 3, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ number_format($estoque->disponivel ?? ($estoque->estoque_gerencial - $estoque->reservado - $estoque->avaria), 3, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ $estoque->ultimo_preco_compra !== null
                                ? 'R$ ' . number_format($estoque->ultimo_preco_compra, 2, ',', '.')
                                : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            {{ $estoque->ultimo_preco_venda !== null
                                ? 'R$ ' . number_format($estoque->ultimo_preco_venda, 2, ',', '.')
                                : '-' }}
                        </td>
                        <td class="px-3 py-2 text-center align-top">
                            @if ($estoque->data_ultima_mov)
                                {{ \Carbon\Carbon::parse($estoque->data_ultima_mov)->format('d/m/Y H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center align-top">
                            <a href="{{ route('estoque.edit', $estoque->id) }}"
                               class="inline-block px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Ajustar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-4 text-center text-gray-500">
                            Nenhum registro de estoque.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($estoques, 'links'))
        <div class="mt-4">
            {{ $estoques->links() }}
        </div>
    @endif
</div>
@endsection
