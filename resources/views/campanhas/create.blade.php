@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Nova Campanha</h1>

    @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('ok') }}
        </div>
    @endif

    <form method="POST" action="{{ route('campanhas.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Nome *</label>
                <input name="nome" value="{{ old('nome') }}" class="w-full border rounded p-2"
                       maxlength="100" required>
                @error('nome')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Tipo *</label>
                <select name="tipo_id" class="w-full border rounded p-2" required>
                    <option value="">Selecione...</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" @selected(old('tipo_id')==$t->id)>{{ $t->descricao }}</option>
                    @endforeach
                </select>
                @error('tipo_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Data Início *</label>
                <input type="date" name="data_inicio" value="{{ old('data_inicio', now()->toDateString()) }}"
                       class="w-full border rounded p-2" required>
                @error('data_inicio')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Data Fim *</label>
                <input type="date" name="data_fim" value="{{ old('data_fim', now()->addMonth()->toDateString()) }}"
                       class="w-full border rounded p-2" required>
                @error('data_fim')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Prioridade *</label>
                <input type="number" name="prioridade" min="1" value="{{ old('prioridade', 1) }}"
                       class="w-full border rounded p-2" required>
                @error('prioridade')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Produto Brinde (opcional)</label>
                <select name="produto_brinde_id" class="w-full border rounded p-2">
                    <option value="">— Nenhum —</option>
                    @foreach($produtos as $p)
                        <option value="{{ $p->id }}" @selected(old('produto_brinde_id')==$p->id)>
                            {{ $p->codfabnumero }} - {{ $p->nome }}
                        </option>
                    @endforeach
                </select>
                @error('produto_brinde_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium">Descrição</label>
            <textarea name="descricao" rows="3" class="w-full border rounded p-2">{{ old('descricao') }}</textarea>
            @error('descricao')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-3 border rounded">
                <div class="font-semibold mb-2">Regras de Cupom</div>

                <div class="mb-2">
                    <label class="block text-sm">Valor base do cupom (tipo 1)</label>
                    <input type="number" step="0.01" name="valor_base_cupom"
                           value="{{ old('valor_base_cupom') }}" class="w-full border rounded p-2">
                    @error('valor_base_cupom')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                </div>

                <div class="mb-2">
                    <label class="block text-sm">Qtd. mínima para cupom (tipo 2)</label>
                    <input type="number" name="quantidade_minima_cupom"
                           value="{{ old('quantidade_minima_cupom') }}" class="w-full border rounded p-2">
                    @error('quantidade_minima_cupom')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                </div>

                <div class="flex items-center gap-4 mt-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="acumulativa_por_valor" value="1"
                               @checked(old('acumulativa_por_valor', true)) class="mr-2">
                        Acumular por valor
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="acumulativa_por_quantidade" value="1"
                               @checked(old('acumulativa_por_quantidade', true)) class="mr-2">
                        Acumular por quantidade
                    </label>
                </div>

                <input type="hidden" name="tipo_acumulacao" value="{{ old('tipo_acumulacao') }}">
            </div>

            <div class="p-3 border rounded">
                <div class="font-semibold mb-2">Opções</div>

                <label class="inline-flex items-center mb-2">
                    <input type="checkbox" name="ativa" value="1" @checked(old('ativa', true)) class="mr-2">
                    Ativa
                </label><br>

                <label class="inline-flex items-center mb-2">
                    <input type="checkbox" name="cumulativa" value="1" @checked(old('cumulativa', false)) class="mr-2">
                    Cumulativa
                </label><br>

                <label class="inline-flex items-center">
                    <input type="checkbox" name="aplicacao_automatica" value="1"
                           @checked(old('aplicacao_automatica', true)) class="mr-2">
                    Aplicação automática
                </label>
            </div>
        </div>


        <div class="pt-2">
            <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                Salvar Campanha
            </button>
            <a href="{{ route('campanhas.index') }}" class="ml-3 text-blue-700 underline">Voltar</a>
        </div>
    </form>
</div>
@endsection
