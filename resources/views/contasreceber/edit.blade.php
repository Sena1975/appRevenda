{{-- resources/views/contasreceber/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Baixar Conta #{{ $conta->id }}</h1>

  @if($errors->any())
    <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">
      <ul class="list-disc ml-5">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  @if(strtoupper($conta->status ?? 'ABERTO') !== 'ABERTO')
    <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">
      Este título não está ABERTO (status atual: {{ strtoupper($conta->status) }}).
    </div>
  @endif

  <div class="bg-white border rounded p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <div class="text-gray-500">Cliente</div>
        <div class="font-semibold">{{ $conta->cliente->nome ?? '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">Pedido</div>
        <div>
          @if($conta->pedido_id)
            <a href="{{ route('vendas.edit', $conta->pedido_id) }}" class="text-blue-600 hover:underline">#{{ $conta->pedido_id }}</a>
          @else
            —
          @endif
        </div>
      </div>
      <div>
        <div class="text-gray-500">Vencimento</div>
        <div>{{ $conta->vencimento ? \Carbon\Carbon::parse($conta->vencimento)->format('d/m/Y') : '—' }}</div>
      </div>
      <div>
        <div class="text-gray-500">Valor do Título (R$)</div>
        <div class="font-semibold">{{ number_format((float)($conta->valor ?? 0), 2, ',', '.') }}</div>
      </div>
    </div>
  </div>

  <form action="{{ route('contasreceber.update', $conta->id) }}" method="POST" class="bg-white border rounded p-4">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Data do Pagamento *</label>
        <input type="date" name="pago_em"
               value="{{ old('pago_em', \Carbon\Carbon::now()->format('Y-m-d')) }}"
               class="border rounded px-3 py-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Valor Pago (R$) *</label>
        <input type="number" step="0.01" min="0.00" name="valor_pago"
               value="{{ old('valor_pago', number_format((float)($conta->valor ?? 0), 2, '.', '')) }}"
               class="border rounded px-3 py-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Forma de Pagamento *</label>
        <select name="forma_pagamento_id" class="border rounded px-3 py-2 w-full" required>
          <option value="">Selecione...</option>
          @foreach($formas as $f)
            <option value="{{ $f->id }}" @selected(old('forma_pagamento_id')==$f->id)>{{ $f->nome }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Status</label>
        <select name="status" class="border rounded px-3 py-2 w-full">
          @php $current = strtoupper(old('status', $conta->status ?? 'ABERTO')); @endphp
          <option value="ABERTO"   @selected($current==='ABERTO')>ABERTO</option>
          <option value="PAGO"  @selected($current==='PAGO')>PAGO</option>
          <option value="CANCELADO"@selected($current==='CANCELADO')>CANCELADO</option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Observação</label>
        <textarea name="observacao" rows="3" class="border rounded px-3 py-2 w-full"
                  placeholder="Ex.: pagou via PIX, abatido R$ X de juros/multa...">{{ old('observacao') }}</textarea>
      </div>
    </div>

    <div class="flex justify-end mt-6 gap-2">
      <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 rounded border hover:bg-gray-50">Cancelar</a>
      <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
        Salvar baixa
      </button>
    </div>
  </form>
</div>
@endsection
