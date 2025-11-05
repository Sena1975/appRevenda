{{-- resources/views/contasreceber/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Parcela #{{ $c->id }}</h1>

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
      <div><strong>Cliente:</strong> {{ $c->cliente_nome ?? '-' }}</div>
      <div><strong>Revendedora:</strong> {{ $c->revendedora_nome ?? '-' }}</div>
      <div><strong>Parcela:</strong> {{ $c->parcela }}/{{ $c->total_parcelas }}</div>
      <div><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}</div>
      <div><strong>Status:</strong> {{ strtoupper((string)$c->status) }}</div>
      <div><strong>Valor:</strong> R$ {{ number_format((float)$c->valor, 2, ',', '.') }}</div>
      <div><strong>Pagamento:</strong> {{ $c->data_pagamento ? \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') : '-' }}</div>
      <div><strong>Valor pago:</strong> {{ is_null($c->valor_pago) ? '-' : 'R$ '.number_format((float)$c->valor_pago, 2, ',', '.') }}</div>
    </div>

    @if(!empty($c->observacao))
      <div class="mt-3 text-sm"><strong>Observações:</strong><br>{{ $c->observacao }}</div>
    @endif
  </div>

  <div class="mt-4 flex items-center gap-2">
    <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">Voltar</a>
    <a href="{{ route('contasreceber.recibo', $c->id) }}" class="px-3 py-2 border rounded">Recibo</a>

    @if(strtoupper((string)$c->status) !== 'PAGO' && strtoupper((string)$c->status) !== 'CANCELADO')
      <a href="{{ route('contasreceber.baixa', $c->id) }}" class="px-3 py-2 bg-blue-600 text-white rounded">Baixar</a>
    @endif

    @if(strtoupper((string)$c->status) === 'PAGO')
      <form action="{{ route('contasreceber.estornar', $c->id) }}" method="POST"
            onsubmit="return confirm('Estornar a baixa desta parcela?');">
        @csrf
        <button type="submit" class="px-3 py-2 bg-orange-600 text-white rounded">Estornar</button>
      </form>
    @endif
  </div>
</div>
@endsection
