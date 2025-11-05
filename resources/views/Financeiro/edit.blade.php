@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h1 class="text-xl font-semibold mb-4">Editar Título #{{ $c->id }}</h1>

  @if($errors->any())
    <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">
      <ul class="list-disc ml-6">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('contasreceber.update',$c->id) }}">
    @csrf @method('PUT')

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Vencimento *</label>
        <input type="date" name="data_vencimento" value="{{ \Carbon\Carbon::parse($c->data_vencimento)->format('Y-m-d') }}" class="w-full border rounded" required>
      </div>
      <div>
        <label class="block text-sm">Valor (R$) *</label>
        <input type="number" step="0.01" name="valor" value="{{ number_format($c->valor,2,'.','') }}" class="w-full border rounded" required>
      </div>
      <div>
        <label class="block text-sm">Forma *</label>
        <select name="forma_pagamento_id" class="w-full border rounded" required>
          @foreach($formas as $f)
            <option value="{{ $f->id }}" @selected($c->forma_pagamento_id==$f->id)>{{ $f->nome }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm">Status *</label>
        @php $st = $c->status; @endphp
        <select name="status" class="w-full border rounded" required>
          <option value="ABERTO"     @selected($st==='ABERTO')>Aberto</option>
          <option value="PAGO"       @selected($st==='PAGO')>Pago</option>
          <option value="CANCELADO"  @selected($st==='CANCELADO')>Cancelado</option>
        </select>
      </div>

      <div>
        <label class="block text-sm">Pago em</label>
        <input type="date" name="data_pagamento" value="{{ $c->data_pagamento ? \Carbon\Carbon::parse($c->data_pagamento)->format('Y-m-d') : '' }}" class="w-full border rounded">
      </div>
      <div>
        <label class="block text-sm">Valor Pago (R$)</label>
        <input type="number" step="0.01" name="valor_pago" value="{{ $c->valor_pago ? number_format($c->valor_pago,2,'.','') : '' }}" class="w-full border rounded">
      </div>

      <div class="col-span-2">
        <label class="block text-sm">Observação</label>
        <textarea name="observacao" rows="3" class="w-full border rounded">{{ $c->observacao }}</textarea>
      </div>
    </div>

    <div class="mt-6 flex gap-2 justify-end">
      <a href="{{ route('contasreceber.show',$c->id) }}" class="px-3 py-2 border rounded">Cancelar</a>
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
    </div>
  </form>
</div>
@endsection
