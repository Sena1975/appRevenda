{{-- resources/views/Financeiro/show.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto p-6">

        <h1 class="text-2xl font-bold mb-4">
            Parcela #{{ $c->id }}
        </h1>

        @if (session('error'))
            <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
        @endif

        <div class="bg-white border rounded p-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Cliente</dt>
                    <dd class="font-medium">{{ $c->cliente_nome ?? $c->cliente_id }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Revendedora</dt>
                    <dd class="font-medium">{{ $c->revendedora_nome ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Pedido</dt>
                    <dd class="font-medium">#{{ $c->pedido_id ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Forma de Pagamento</dt>
                    <dd class="font-medium">#{{ $c->forma_pagamento_id ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-gray-500">Emissão</dt>
                    <dd class="font-medium">
                        {{ $c->data_emissao ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y') : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Vencimento</dt>
                    <dd class="font-medium">
                        {{ $c->data_vencimento ? \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') : '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-gray-500">Valor</dt>
                    <dd class="font-medium">
                        R$ {{ number_format((float) $c->valor, 2, ',', '.') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @php $st = strtoupper((string)$c->status); @endphp
                        @if ($st === 'PAGO')
                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">PAGO</span>
                        @elseif($st === 'CANCELADO')
                            <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800">CANCELADO</span>
                        @else
                            <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">ABERTO</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-gray-500">Pago em</dt>
                    <dd class="font-medium">
                        {{ $c->data_pagamento ? \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Valor pago</dt>
                    <dd class="font-medium">
                        {{ $c->valor_pago !== null ? 'R$ ' . number_format((float) $c->valor_pago, 2, ',', '.') : '-' }}
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Observação</dt>
                    <dd class="font-medium whitespace-pre-line">{{ $c->observacao ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            {{-- Voltar --}}
            <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 rounded border text-gray-700 hover:bg-gray-50">
                Voltar
            </a>

            {{-- Editar dados básicos --}}
            <a href="{{ route('contasreceber.edit', $c->id) }}"
                class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                Editar
            </a>

            @php $statusUpper = strtoupper((string)$c->status); @endphp

            {{-- Baixar (se NÃO estiver PAGO) --}}
            @if ($statusUpper !== 'PAGO')
                <a href="{{ route('contasreceber.baixa', $c->id) }}"
                    class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    Baixar
                </a>
            @endif

            {{-- Estornar & Recibo (se PAGO) --}}
            @if ($statusUpper === 'PAGO')
                <form action="{{ route('contasreceber.estornar', $c->id) }}" method="POST"
                    onsubmit="return confirm('Estornar esta baixa?');" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded bg-orange-600 text-white hover:bg-orange-700">
                        Estornar
                    </button>
                </form>

                <a href="{{ route('contasreceber.recibo', $c->id) }}"
                    class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                    Recibo (HTML)
                </a>

                {{-- Se quiser PDF (com barryvdh/laravel-dompdf instalado) --}}
                <a href="{{ route('contasreceber.recibo', ['id' => $c->id, 'pdf' => 1]) }}"
                    class="px-3 py-2 rounded bg-emerald-700 text-white hover:bg-emerald-800">
                    Recibo (PDF)
                </a>
            @endif
        </div>
    </div>
@endsection
