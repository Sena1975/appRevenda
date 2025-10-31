{{-- resources/views/vendas/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Pedidos de Venda</h1>
        <a href="{{ route('vendas.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Novo Pedido</a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-3 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-3 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-3 p-3 rounded bg-blue-100 text-blue-800">{{ session('info') }}</div>
    @endif

    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full">
            <thead class="bg-gray-50 text-sm">
                <tr>
                    <th class="px-3 py-2 text-left w-16">#</th>
                    <th class="px-3 py-2 text-left">Cliente</th>
                    <th class="px-3 py-2 text-left">Revendedora</th>
                    <th class="px-3 py-2 text-left w-36">Data</th>
                    <th class="px-3 py-2 text-left w-32">Status</th>
                    <th class="px-3 py-2 text-right w-32">Total (R$)</th>
                    <th class="px-3 py-2 text-center w-48">Ações</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($pedidos as $p)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $p->id }}</td>
                        <td class="px-3 py-2">{{ $p->cliente->nome ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $p->revendedora->nome ?? '-' }}</td>
                        <td class="px-3 py-2">
                            {{ \Carbon\Carbon::parse($p->data_pedido)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $status = strtoupper($p->status ?? 'PENDENTE');
                                $badge  = match($status){
                                    'ENTREGUE'  => 'bg-green-100 text-green-800',
                                    'CANCELADO' => 'bg-red-100 text-red-800',
                                    default     => 'bg-yellow-100 text-yellow-800',
                                };
                            @endphp
                            <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{ number_format((float)($p->valor_liquido ?? $p->valor_total ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Editar --}}
                                <a href="{{ route('vendas.edit', $p->id) }}"
                                   class="px-2 py-1 text-xs rounded border hover:bg-gray-50"
                                   title="Editar">
                                   Editar
                                </a>

                                {{-- Exportar CSV --}}
                                <a href="{{ route('vendas.exportar', $p->id) }}"
                                   class="px-2 py-1 text-xs rounded border hover:bg-gray-50"
                                   title="Exportar CSV">
                                   Exportar
                                </a>

                                {{-- Excluir --}}
                                <form action="{{ route('vendas.destroy', $p->id) }}" method="POST"
                                      onsubmit="return confirm('Excluir o pedido #{{ $p->id }}? Esta ação não pode ser desfeita.');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-2 py-1 text-xs rounded border border-red-500 text-red-600 hover:bg-red-50"
                                            title="Excluir">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-center text-gray-500" colspan="7">
                            Nenhum pedido encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if(method_exists($pedidos, 'links'))
        <div class="mt-4">
            {{ $pedidos->links() }}
        </div>
    @endif
</div>
@endsection
