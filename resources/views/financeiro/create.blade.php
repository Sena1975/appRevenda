@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-6 bg-white border rounded">
  <h1 class="text-xl font-semibold mb-4">Nova Parcela (manual)</h1>

  @if($errors->any())
    <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
      <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('contasreceber.store') }}" class="grid grid-cols-1 gap-3">
    @csrf

    <div>
      <label class="text-sm">Pedido # *</label>
      <input type="number" name="pedido_id" class="w-full border rounded" required>
    </div>

    <div>
      <label class="text-sm">Cliente ID *</label>
      <input type="number" name="cliente_id" class="w-full border rounded" required>
    </div>

    <div>
      <label class="text-sm">Revendedora ID</label>
      <input type="number" name="revendedora_id" class="w-full border rounded">
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-sm">Parcela *</label>
        <input type="number" name="parcela" value="1" class="w-full border rounded" required>
      </div>
      <div>
        <label class="text-sm">Total de parcelas *</label>
        <input type="number" name="total_parcelas" value="1" class="w-full border rounded" required>
      </div>
    </div>

    <div>
      <label class="text-sm">Forma de pagamento *</label>
      <select name="forma_pagamento_id" class="w-full border rounded" required>
        @foreach($formas as $f)
          <option value="{{ $f->id }}">{{ $f->nome }}</option>
        @endforeach
      </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-sm">Emissão *</label>
        <input type="date" name="data_emissao" value="{{ date('Y-m-d') }}" class="w-full border rounded" required>
      </div>
      <div>
        <label class="text-sm">Vencimento *</label>
        <input type="date" name="data_vencimento" value="{{ date('Y-m-d') }}" class="w-full border rounded" required>
      </div>
    </div>

    <div>
      <label class="text-sm">Valor *</label>
      <input type="number" step="0.01" name="valor" class="w-full border rounded" required>
    </div>

    <div>
      <label class="text-sm">Observação</label>
      <textarea name="observacao" rows="3" class="w-full border rounded"></textarea>
    </div>

    <div class="flex justify-end gap-2">
      <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">Cancelar</a>
      <button class="px-4 py-2 rounded bg-blue-600 text-white">Salvar</button>
    </div>
  </form>
</div>
@endsection
