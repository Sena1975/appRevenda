{{-- resources/views/relatorios/extrato_cliente.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Extrato de Cliente (Contas a Receber)</h1>

    {{-- Mensagens flash --}}
    @if(session('success'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
    @endif

    {{-- Filtros --}}
    @php
        $f = $filtros ?? [];
        $statusSel = $f['status'] ?? 'TODOS';
    @endphp

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded border mb-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Cliente</label>
            <select name="cliente_id" class="w-full border rounded px-2 py-1" required>
                <option value="">Selecione...</option>
                @foreach($clientes as $cli)
                    <option value="{{ $cli->id }}"
                        @selected((int)($f['cliente_id'] ?? 0) === (int)$cli->id)>
                        {{ $cli->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Vencimento de</label>
            <input type="date" name="data_de"
                   value="{{ $f['data_de'] ?? '' }}"
                   class="w-full border rounded px-2 py-1">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Até</label>
            <input type="date" name="data_ate"
                   value="{{ $f['data_ate'] ?? '' }}"
                   class="w-full border rounded px-2 py-1">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full border rounded px-2 py-1">
                <option value="TODOS"     @selected($statusSel === 'TODOS')>Todos</option>
                <option value="ABERTO"    @selected($statusSel === 'ABERTO')>Em aberto</option>
                <option value="PAGO"      @selected($statusSel === 'PAGO')>Pago</option>
                <option value="CANCELADO" @selected($statusSel === 'CANCELADO')>Cancelado</option>
                {{-- se quiser, pode filtrar por situacao depois (EM ATRASO / A VENCER) --}}
            </select>
        </div>

        <div class="md:col-span-4 flex items-end gap-2">
            <button class="px-3 py-2 bg-blue-600 text-white rounded text-sm">
                Filtrar
            </button>
            <a href="{{ route('relatorios.recebimentos.extrato_cliente') }}"
               class="px-3 py-2 border rounded text-sm">
                Limpar
            </a>
        </div>
    </form>

    @if(empty($f['cliente_id']))
        <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded text-sm">
            Selecione um cliente e um período (opcional) para visualizar o extrato.
        </div>
    @else
        {{-- Resumo do extrato --}}
        @php
            $saldoAnterior = (float)($saldoAnterior ?? 0);
            $totTitulo     = (float)($totais['titulo'] ?? 0);
            $totPago       = (float)($totais['pago'] ?? 0);
            $totSaldo      = (float)($totais['saldo'] ?? 0);
            $saldoFinal    = $saldoAnterior + $totSaldo;
        @endphp

        <div class="bg-white border rounded p-4 mb-4 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-gray-500">Cliente</div>
                    @php
                        $cliSel = $clientes->firstWhere('id', (int)$f['cliente_id']);
                    @endphp
                    <div class="font-semibold">{{ $cliSel->nome ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Período</div>
                    <div>
                        {{ $f['data_de'] ? \Carbon\Carbon::parse($f['data_de'])->format('d/m/Y') : 'Início' }}
                        &nbsp;até&nbsp;
                        {{ $f['data_ate'] ? \Carbon\Carbon::parse($f['data_ate'])->format('d/m/Y') : 'Hoje' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Status filtrado</div>
                    <div>{{ $statusSel === 'TODOS' ? 'Todos' : $statusSel }}</div>
                </div>
            </div>

            <hr class="my-3">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-gray-500">Saldo anterior ao período</div>
                    <div class="font-semibold">
                        R$ {{ number_format($saldoAnterior, 2, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Total de títulos no período</div>
                    <div class="font-semibold">
                        R$ {{ number_format($totTitulo, 2, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Total pago no período</div>
                    <div class="font-semibold">
                        R$ {{ number_format($totPago, 2, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Saldo final (anterior + saldo período)</div>
                    <div class="font-semibold">
                        R$ {{ number_format($saldoFinal, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabela do extrato --}}
        <div class="overflow-x-auto bg-white border rounded">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">Vencimento</th>
                        <th class="px-2 py-2 text-left">Pagamento</th>
                        <th class="px-2 py-2 text-left">Parcela</th>
                        <th class="px-2 py-2 text-left">Forma / Plano</th>
                        <th class="px-2 py-2 text-left">Status</th>
                        <th class="px-2 py-2 text-right">Valor Título</th>
                        <th class="px-2 py-2 text-right">Valor Pago</th>
                        <th class="px-2 py-2 text-right">Saldo</th>
                        <th class="px-2 py-2 text-right">Saldo Acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $saldoAcumulado = $saldoAnterior;
                    @endphp

                    @forelse($movimentos as $m)
                        @php
                            $status = strtoupper((string)($m->status_titulo ?? ''));
                            $saldoAcumulado += (float)($m->saldo ?? 0);
                        @endphp
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-2 py-1">
                                @if(!empty($m->data_vencimento))
                                    {{ \Carbon\Carbon::parse($m->data_vencimento)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                @if(!empty($m->data_pagamento))
                                    {{ \Carbon\Carbon::parse($m->data_pagamento)->format('d/m/Y') }}
                                @else
                                    &ndash;
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                {{ $m->parcela }}/{{ $m->total_parcelas }}
                                @if($m->pedido_id)
                                    <span class="text-gray-400 text-[10px]">
                                        (Pedido #{{ $m->pedido_id }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                {{ $m->forma_pagamento ?? '-' }}
                                @if(!empty($m->plano_pagamento))
                                    <span class="text-gray-400 text-[11px]">
                                        – {{ $m->plano_pagamento }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                <span class="px-2 py-0.5 rounded text-[11px]
                                    @if($status === 'PAGO') bg-green-100 text-green-700
                                    @elseif($status === 'CANCELADO') bg-red-100 text-red-700
                                    @else bg-yellow-100 text-yellow-700 @endif">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-2 py-1 text-right">
                                {{ number_format((float)($m->valor_titulo ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 text-right">
                                {{ is_null($m->valor_pago)
                                    ? '—'
                                    : number_format((float)$m->valor_pago, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 text-right">
                                {{ number_format((float)($m->saldo ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 text-right font-semibold">
                                {{ number_format($saldoAcumulado, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-4 text-center text-gray-500">
                                Nenhum título encontrado para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
