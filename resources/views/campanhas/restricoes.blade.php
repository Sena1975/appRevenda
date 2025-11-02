@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-2">
        Restrições da Campanha #{{ $campanha->id }} — {{ $campanha->nome }}
    </h1>
    <p class="text-sm text-gray-600 mb-6">
        Adicione produto, codfab ou categoria para limitar onde esta campanha se aplica. <br>
        Se não houver nenhuma restrição, a campanha vale para todos os itens do pedido.
    </p>

    @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('ok') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('campanhas.restricoes.store', $campanha->id) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Produto (opcional)</label>
            <select name="produto_id" class="w-full border rounded p-2">
                <option value="">— Selecionar —</option>
                @foreach($produtos as $p)
                    <option value="{{ $p->id }}" @selected(old('produto_id')==$p->id)>
                        {{ $p->codfabnumero }} — {{ $p->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Codfab (opcional)</label>
            <input name="codfabnumero" value="{{ old('codfabnumero') }}" class="w-full border rounded p-2" maxlength="30" placeholder="Ex.: NATBRA-133863">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Categoria (opcional)</label>
            <select name="categoria_id" class="w-full border rounded p-2">
                <option value="">— Selecionar —</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" @selected(old('categoria_id')==$c->id)>{{ $c->nome }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Qtd. mínima *</label>
            <input type="number" name="quantidade_minima" value="{{ old('quantidade_minima', 1) }}" min="1" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Peso participação *</label>
            <input type="number" step="0.01" name="peso_participacao" value="{{ old('peso_participacao', 1) }}" min="0" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Observação</label>
            <input name="observacao" value="{{ old('observacao') }}" class="w-full border rounded p-2">
        </div>

        <div class="md:col-span-3">
            <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Adicionar</button>
            <a href="{{ route('campanhas.create') }}" class="ml-3 text-blue-700 underline">Voltar</a>
        </div>
    </form>

    <div class="bg-white border rounded">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="p-2">#</th>
                    <th class="p-2">Produto</th>
                    <th class="p-2">Codfab</th>
                    <th class="p-2">Categoria</th>
                    <th class="p-2 text-center">Qtd mínima</th>
                    <th class="p-2 text-center">Peso</th>
                    <th class="p-2">Obs</th>
                    <th class="p-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restricoes as $r)
                    <tr class="border-t">
                        <td class="p-2">{{ $r->id }}</td>
                        <td class="p-2">{{ $r->produto?->nome ?? '—' }}</td>
                        <td class="p-2">{{ $r->codfabnumero ?? '—' }}</td>
                        <td class="p-2">{{ $r->categoria?->nome ?? '—' }}</td>
                        <td class="p-2 text-center">{{ $r->quantidade_minima }}</td>
                        <td class="p-2 text-center">{{ $r->peso_participacao }}</td>
                        <td class="p-2">{{ $r->observacao }}</td>
                        <td class="p-2 text-center">
                            <form method="POST" action="{{ route('campanhas.restricoes.destroy', [$campanha->id, $r->id]) }}"
                                  onsubmit="return confirm('Remover esta restrição?')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-xs">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t">
                        <td class="p-2" colspan="8">Nenhuma restrição. A campanha se aplica a todos os itens.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
