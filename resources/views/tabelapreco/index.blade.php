{{-- resources/views/tabelapreco/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-800">Tabela de Preços</h1>
        <a href="{{ route('tabelapreco.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Novo Preço
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Cód. Fábrica</th>
                    <th class="px-4 py-2 text-left">Produto</th>
                    <th class="px-4 py-2 text-left">Preço Compra</th>
                    <th class="px-4 py-2 text-left">Preço Revenda</th>
                    <th class="px-4 py-2 text-left">Pontuação</th>
                    <th class="px-4 py-2 text-left">Data Início</th>
                    <th class="px-4 py-2 text-left">Data Fim</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tabelas as $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $item->codfab ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $item->produto->nome ?? '—' }}</td>
                        <td class="px-4 py-2">R$ {{ number_format($item->preco_compra, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">R$ {{ number_format($item->preco_revenda, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ $item->pontuacao }}</td>
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($item->data_inicio)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($item->data_fim)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-white {{ $item->status ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $item->status ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('tabelapreco.edit', $item->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('tabelapreco.destroy', $item->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Excluir este registro?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">Nenhum registro encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tabelas,'links'))
        <div class="mt-4">
            {{ $tabelas->links() }}
        </div>
    @endif
</div>
@endsection
