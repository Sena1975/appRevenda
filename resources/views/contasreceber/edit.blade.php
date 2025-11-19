{{-- resources/views/contasreceber/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Editar Conta #{{ $c->id }}</h1>

    {{-- Erros de validação --}}
    @if($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $statusAtual  = strtoupper($c->status ?? 'ABERTO');
        $clienteAtual = optional($clientes->firstWhere('id', $c->cliente_id))->nome;
        $formaAtual   = optional($formas->firstWhere('id', $c->forma_pagamento_id))->nome;
        $dataVenc     = $c->data_vencimento
            ? \Carbon\Carbon::parse($c->data_vencimento)->format('Y-m-d')
            : '';
    @endphp

    {{-- Aviso de status (caso não esteja ABERTO) --}}
    @if($statusAtual !== 'ABERTO')
        <div class="mb-3 p-3 bg-yellow-100 text-yellow-800 rounded text-sm">
            Atenção: esta parcela está com status <strong>{{ $statusAtual }}</strong>.
            A edição aqui ajusta apenas dados básicos (cliente, forma, vencimento e valor).
            Baixa/estorno continuam sendo feitos na tela de <strong>Baixa</strong>.
        </div>
    @endif

    {{-- Resumo da conta --}}
    <div class="bg-white border rounded p-4 mb-4 text-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-gray-500">Cliente atual</div>
                <div class="font-semibold">{{ $clienteAtual ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Forma de pagamento atual</div>
                <div>{{ $formaAtual ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Status</div>
                <div class="font-semibold">{{ $statusAtual }}</div>
            </div>
            <div>
                <div class="text-gray-500">Valor atual (R$)</div>
                <div class="font-semibold">
                    {{ number_format((float)($c->valor ?? 0), 2, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Formulário de edição --}}
    <form action="{{ route('contasreceber.update', $c->id) }}" method="POST" class="bg-white border rounded p-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Cliente --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Cliente *</label>
                <select name="cliente_id" class="border rounded px-3 py-2 w-full" required>
                    <option value="">Selecione...</option>
                    @foreach($clientes as $cli)
                        <option value="{{ $cli->id }}"
                            @selected(old('cliente_id', $c->cliente_id) == $cli->id)>
                            {{ $cli->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Forma de pagamento --}}
            <div>
                <label class="block text-sm font-medium mb-1">Forma de Pagamento *</label>
                <select name="forma_pagamento_id" class="border rounded px-3 py-2 w-full" required>
                    <option value="">Selecione...</option>
                    @foreach($formas as $f)
                        <option value="{{ $f->id }}"
                            @selected(old('forma_pagamento_id', $c->forma_pagamento_id) == $f->id)>
                            {{ $f->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Vencimento --}}
            <div>
                <label class="block text-sm font-medium mb-1">Data de Vencimento *</label>
                <input type="date"
                       name="data_vencimento"
                       value="{{ old('data_vencimento', $dataVenc) }}"
                       class="border rounded px-3 py-2 w-full"
                       required>
            </div>

            {{-- Valor --}}
            <div>
                <label class="block text-sm font-medium mb-1">Valor do Título (R$) *</label>
                <input type="text"
                       name="valor"
                       value="{{ old('valor', number_format((float)($c->valor ?? 0), 2, ',', '.')) }}"
                       class="border rounded px-3 py-2 w-full"
                       required>
            </div>

            {{-- Observação --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Observação</label>
                <textarea name="observacao" rows="3"
                          class="border rounded px-3 py-2 w-full"
                          placeholder="Alguma anotação sobre este título...">{{ old('observacao', $c->observacao) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end mt-6 gap-2">
            <a href="{{ route('contasreceber.index') }}"
               class="px-3 py-2 rounded border hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                Salvar alterações
            </button>
        </div>
    </form>
</div>
@endsection
