{{-- resources/views/planopagamento/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Editar Plano: {{ $plano->descricao }}</h1>

    @if($errors->any())
        <div class="mb-3 p-3 rounded bg-red-100 text-red-800">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('planopagamento.update', $plano->id) }}" method="POST" class="bg-white border rounded p-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Código do Plano *</label>
                <input type="text" name="codplano" value="{{ old('codplano', $plano->codplano) }}"
                       class="w-full border rounded px-3 py-2" maxlength="20" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao', $plano->descricao) }}"
                       class="w-full border rounded px-3 py-2" maxlength="100" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Forma de Pagamento *</label>
                <select name="formapagamento_id" class="w-full border rounded px-3 py-2" required>
                    @foreach($formas as $f)
                        <option value="{{ $f->id }}" @selected(old('formapagamento_id', $plano->formapagamento_id)==$f->id)>
                            {{ $f->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Parcelas</label>
                <input type="number" min="1" name="parcelas" value="{{ old('parcelas', $plano->parcelas) }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Prazo 1 (dias)</label>
                <input type="number" min="0" name="prazo1" value="{{ old('prazo1', $plano->prazo1) }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Prazo 2 (dias)</label>
                <input type="number" min="0" name="prazo2" value="{{ old('prazo2', $plano->prazo2) }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Prazo 3 (dias)</label>
                <input type="number" min="0" name="prazo3" value="{{ old('prazo3', $plano->prazo3) }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Prazo Médio (dias)</label>
                <input type="number" min="0" name="prazomedio" value="{{ old('prazomedio', $plano->prazomedio) }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" name="ativo" value="1" id="ativo" class="h-4 w-4"
                       {{ old('ativo', (int)($plano->ativo ?? 1)) ? 'checked' : '' }}>
                <label for="ativo" class="text-sm">Ativo</label>
            </div>
        </div>

        <div class="flex justify-end mt-6 gap-2">
            <a href="{{ route('planopagamento.index') }}" class="px-3 py-2 rounded border hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                Salvar alterações
            </button>
        </div>
    </form>
</div>
@endsection
