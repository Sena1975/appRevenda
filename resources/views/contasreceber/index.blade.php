@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Contas a Receber</h1>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white p-4 rounded-lg shadow flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500">Buscar</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cliente, Revendedora, Obs ou #Pedido"
                   class="border-gray-300 rounded-md w-64">
        </div>

        <div>
            <label class="block text-xs text-gray-500">Status</label>
            <select name="status" class="border-gray-300 rounded-md">
                <option value="">Todos</option>
                @foreach(['ABERTO','BAIXADO','CANCELADO'] as $st)
                    <option value="{{ $st }}" @selected($status===$st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500">Forma</label>
            <select name="forma_pagamento_id" class="border-gray-300 rounded-md">
                <option value="">Todas</option>
                @foreach($formas as $f)
                    <option value="{{ $f->id }}" @selected((string)$formaId===(string)$f->id)>{{ $f->nome }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500"># Pedido</label>
            <input type="number" name="pedido_id" value="{{ $pedido }}" class="border-gray-300 rounded-md w-28">
        </div>

        <div>
            <label class="block text-xs text-gray-500">Venc. Inicial</label>
            <input type="date" name="ini" value="{{ $ini }}" class="border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-xs text-gray-500">Venc. Final</label>
            <input type="date" name="fim" value="{{ $fim }}" class="border-gray-300 rounded-md">
        </div>

        <div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Filtrar</button>
        </div>
        <div>
            <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 bg-gray-100 border rounded-lg hover:bg-gray-200">Limpar</a>
        </div>
    </form>

    <!-- Cards de totalizadores -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">ABERTO</div>
            <div class="text-lg font-semibold">R$ {{ number_format($totalAberto,2,',','.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">BAIXADO</div>
            <div class="text-lg font-semibold">R$ {{ number_format($totalBaixado,2,',','.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">CANCELADO</div>
            <div class="text-lg font-semibold">R$ {{ number_format($totalCancelado,2,',','.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">TOTAL (Filtro)</div>
            <div class="text-lg font-semibold">R$ {{ number_format($totalGeral,2,',','.') }}</div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left">Parc.</th>
                    <th class="px-3 py-2 text-left">Pedido</th>
                    <th class="px-3 py-2 text-left">Cliente</th>
                    <th class="px-3 py-2 text-left">Revendedora</th>
                    <th class="px-3 py-2 text-left">Emissão</th>
                    <th class="px-3 py-2 text-left">Vencimento</th>
                    <th class="px-3 py-2 text-right">Valor</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-left">Obs</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($parcelas as $c)
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $c->parcela }}/{{ $c->total_parcelas }}</td>
                        <td class="px-3 py-2">
                            <a class="text-blue-600 hover:underline" href="{{ route('vendas.edit', $c->pedido_id) }}">#{{ $c->pedido_id }}</a>
                        </td>
                        <td class="px-3 py-2">{{ $c->cliente->nome ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $c->revendedora->nome ?? '-' }}</td>
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right">R$ {{ number_format($c->valor,2,',','.') }}</td>
                        <td class="px-3 py-2 text-center">
                            @php
                                $badge = [
                                    'ABERTO'    => 'bg-yellow-100 text-yellow-700',
                                    'BAIXADO'   => 'bg-green-100 text-green-700',
                                    'CANCELADO' => 'bg-red-100 text-red-700',
                                ][$c->status] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-2 py-1 text-xs rounded {{ $badge }}">{{ $c->status }}</span>
                        </td>
                        <td class="px-3 py-2 truncate max-w-[280px]" title="{{ $c->observacao }}">{{ $c->observacao }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-gray-500">Nenhuma parcela encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2">
            {{ $parcelas->links() }}
        </div>
    </div>
</div>
@endsection
