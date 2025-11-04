<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Contas a Receber</h2>
    </x-slot>

    <div class="space-y-6">

        {{-- Filtros --}}
        <form method="GET" class="bg-white shadow rounded-lg p-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="text-xs text-gray-600">Cliente</label>
                <select name="cliente_id" class="w-full border-gray-300 rounded">
                    <option value="">Todos</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" @selected(request('cliente_id')==$c->id)>{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-600">Revendedora</label>
                <select name="revendedora_id" class="w-full border-gray-300 rounded">
                    <option value="">Todas</option>
                    @foreach($revendedoras as $r)
                        <option value="{{ $r->id }}" @selected(request('revendedora_id')==$r->id)>{{ $r->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-600">Forma</label>
                <select name="forma_pagamento_id" class="w-full border-gray-300 rounded">
                    <option value="">Todas</option>
                    @foreach($forma as $f)
                        <option value="{{ $f->id }}" @selected(request('forma_pagamento_id')==$f->id)>{{ $f->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border-gray-300 rounded">
                    @php $st = request('status'); @endphp
                    <option value="">Todos</option>
                    <option value="ABERTO"  @selected($st==='ABERTO')>Aberto</option>
                    <option value="PAGO"    @selected($st==='PAGO')>Pago</option>
                    <option value="VENCIDO" @selected($st==='VENCIDO')>Vencido</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-600">Venc. de</label>
                <input type="date" name="vencimento_de" value="{{ request('vencimento_de') }}" class="w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="text-xs text-gray-600">Venc. até</label>
                <input type="date" name="vencimento_ate" value="{{ request('vencimento_ate') }}" class="w-full border-gray-300 rounded">
            </div>

            <div class="md:col-span-6 flex gap-2 justify-end">
                <input type="text" name="pedido_id" value="{{ request('pedido_id') }}" placeholder="Pedido #"
                       class="border-gray-300 rounded w-36 text-sm px-2">
                <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 bg-gray-100 rounded text-sm">Limpar</a>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Filtrar</button>
            </div>
        </form>

        {{-- Totais --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white shadow rounded p-4">
                <div class="text-xs text-gray-500">Total Geral</div>
                <div class="text-lg font-semibold">R$ {{ number_format($total_geral,2,',','.') }}</div>
            </div>
            <div class="bg-white shadow rounded p-4">
                <div class="text-xs text-gray-500">Em Aberto</div>
                <div class="text-lg font-semibold text-yellow-700">R$ {{ number_format($total_aberto,2,',','.') }}</div>
            </div>
            <div class="bg-white shadow rounded p-4">
                <div class="text-xs text-gray-500">Recebido</div>
                <div class="text-lg font-semibold text-green-700">R$ {{ number_format($total_pago,2,',','.') }}</div>
            </div>
            <div class="bg-white shadow rounded p-4">
                <div class="text-xs text-gray-500">Vencido</div>
                <div class="text-lg font-semibold text-red-700">R$ {{ number_format($total_vencido,2,',','.') }}</div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Parc.</th>
                        <th class="px-3 py-2 text-left">Pedido</th>
                        <th class="px-3 py-2 text-left">Cliente</th>
                        <th class="px-3 py-2 text-left">Forma</th>
                        <th class="px-3 py-2 text-center">Vencimento</th>
                        <th class="px-3 py-2 text-right">Valor (R$)</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contas as $c)
                        @php
                            $vencido = $c->status === 'ABERTO' && $c->data_vencimento < $hoje;
                        @endphp
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $c->parcela }}/{{ $c->total_parcelas }}</td>
                            <td class="px-3 py-2">#{{ $c->pedido_id }}</td>
                            <td class="px-3 py-2">{{ $c->cliente->nome ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $c->forma->nome ?? '—' }}</td>
                            <td class="px-3 py-2 text-center {{ $vencido ? 'text-red-700 font-semibold' : '' }}">
                                {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format($c->valor,2,',','.') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($vencido)
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">VENCIDO</span>
                                @elseif($c->status==='PAGO')
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">PAGO</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">ABERTO</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Nenhuma conta encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-3">
                {{ $contas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
