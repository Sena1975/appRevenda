{{-- resources/views/contasreceber/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">
    Parcela #{{ str_pad($c->id, 6, '0', STR_PAD_LEFT) }}
  </h1>

  @if(session('success'))
    <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
  @endif
  @if(session('info'))
    <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
  @endif

  <div class="bg-white border rounded p-4">
    <div class="grid grid-cols-2 gap-3 text-sm">

      <div>
        <strong>Cliente:</strong><br>
        {{ $c->cliente_nome ?? '-' }}
      </div>

      <div>
        <strong>Revendedora:</strong><br>
        {{ $c->revendedora_nome ?? '-' }}
      </div>

      <div>
        <strong>Forma de pagamento:</strong><br>
        {{ $c->forma_pagamento ?? '-' }}
      </div>

      <div>
        <strong>Plano de pagamento:</strong><br>
        {{ $c->plano_pagamento ?? '-' }}
      </div>

      <div>
        <strong>Parcela:</strong><br>
        {{ $c->parcela }}/{{ $c->total_parcelas }}
      </div>

      <div>
        <strong>Pedido:</strong><br>
        {{ $c->pedido_id ?? '-' }}
      </div>

      <div>
        <strong>Emissão:</strong><br>
        @if(!empty($c->data_emissao))
          {{ \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y') }}
        @else
          &ndash;
        @endif
      </div>

      <div>
        <strong>Vencimento:</strong><br>
        @if(!empty($c->data_vencimento))
          {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
        @else
          &ndash;
        @endif
      </div>

      <div>
        <strong>Status título:</strong><br>
        {{ strtoupper((string)$c->status) }}
      </div>

      <div>
        <strong>Situação:</strong><br>
        {{ $c->situacao ?? '-' }}
      </div>

      <div>
        <strong>Valor título:</strong><br>
        R$ {{ number_format((float)$c->valor, 2, ',', '.') }}
      </div>

      <div>
        <strong>Saldo em aberto:</strong><br>
        R$ {{ number_format((float)($c->saldo ?? 0), 2, ',', '.') }}
      </div>

      <div>
        <strong>Pagamento:</strong><br>
        @if(!empty($c->data_pagamento))
          {{ \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') }}
        @else
          &ndash;
        @endif
      </div>

      <div>
        <strong>Valor pago:</strong><br>
        {{ is_null($c->valor_pago)
            ? '–'
            : 'R$ '.number_format((float)$c->valor_pago, 2, ',', '.') }}
      </div>
    </div>

    @if(!empty($c->observacao ?? null))
      <div class="mt-3 text-sm">
        <strong>Observações:</strong><br>
        {{ $c->observacao }}
      </div>
    @endif
  </div>

  <div class="mt-4 flex flex-wrap items-center gap-2">
    <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">
      Voltar
    </a>

    <a href="{{ route('contasreceber.recibo', $c->id) }}" class="px-3 py-2 border rounded">
      Recibo
    </a>

    @php $statusUpper = strtoupper((string)$c->status); @endphp

    @if($statusUpper !== 'PAGO' && $statusUpper !== 'CANCELADO')
      <a href="{{ route('contasreceber.baixa', $c->id) }}"
         class="px-3 py-2 bg-blue-600 text-white rounded">
        Baixar
      </a>
    @endif

    @if($statusUpper === 'PAGO')
      <form action="{{ route('contasreceber.estornar', $c->id) }}" method="POST"
            onsubmit="return confirm('Estornar a baixa desta parcela?');">
        @csrf
        <button type="submit" class="px-3 py-2 bg-orange-600 text-white rounded">
          Estornar
        </button>
      </form>
    @endif
  </div>
</div>
@endsection
