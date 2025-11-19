@extends('layouts.app')

@section('content')
    <div class="max-w-xl mx-auto p-6 bg-white rounded shadow">

        <h1>Baixar parcela #{{ $conta->id }}</h1>
        <p><strong>Cliente:</strong> {{ $conta->cliente_nome ?? '-' }}</p>
        <p><strong>Valor:</strong> R$ {{ number_format((float) $conta->valor, 2, ',', '.') }}</p>
        <p><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}</p>
        <p><strong>Status atual:</strong>
            <span
                class="px-2 py-0.5 rounded text-xs
                {{ $conta->status === 'ABERTO' ? 'bg-yellow-100 text-yellow-800' : ($conta->status === 'PAGO' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') }}">
                {{ $conta->status }}
            </span>
        </p>


        @if (session('error'))
            <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('contasreceber.baixa.store', $conta->id) }}">
            @csrf

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" value="{{ old('data_pagamento', date('Y-m-d')) }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Valor Pago</label>
                    <input type="text" inputmode="decimal" name="valor_pago"
                        value="{{ old('valor_pago', number_format((float) $conta->valor, 2, ',', '.')) }}"
                        class="w-full border rounded px-3 py-2 text-right" placeholder="Ex.: 91,25">
                    <p class="text-xs text-gray-500 mt-1">Aceita 91,25 • 91.25 • 1.234,56</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Observação</label>
                    <textarea name="observacao" rows="3" class="w-full border rounded px-3 py-2" placeholder="Opcional">{{ old('observacao') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('contasreceber.index') }}" class="px-3 py-2 border rounded">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Confirmar Baixa
                </button>
            </div>
        </form>
    </div>
@endsection
