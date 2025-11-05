{{-- resources/views/planopagamento/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Planos de Pagamento</h1>
        <a href="{{ route('planopagamento.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Novo Plano
        </a>
    </div>

    @if(session('success'))
        <div class="mb-3 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-3 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left w-28">Código</th>
                    <th class="px-3 py-2 text-left">Descrição</th>
                    <th class="px-3 py-2 text-left">Forma</th>
                    <th class="px-3 py-2 text-right w-24">Parcelas</th>
                    <th class="px-3 py-2 text-right w-24">Prazo 1</th>
                    <th class="px-3 py-2 text-right w-24">Prazo 2</th>
                    <th class="px-3 py-2 text-right w-24">Prazo 3</th>
                    <th class="px-3 py-2 text-center w-24">Ativo</th>
                    <th class="px-3 py-2 text-center w-40">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($planos as $p)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $p->codplano }}</td>
                        <td class="px-3 py-2">{{ $p->descricao }}</td>
                        <td class="px-3 py-2">{{ $p->formaPagamento->nome ?? ('#'.$p->formapagamento_id) }}</td>
                        <td class="px-3 py-2 text-right">{{ (int)($p->parcelas ?? 0) }}</td>
                        <td class="px-3 py-2 text-right">{{ (int)($p->prazo1 ?? 0) }}</td>
                        <td class="px-3 py-2 text-right">{{ (int)($p->prazo2 ?? 0) }}</td>
                        <td class="px-3 py-2 text-right">{{ (int)($p->prazo3 ?? 0) }}</td>
                        <td class="px-3 py-2 text-center">
                            @php $ativo = (int)($p->ativo ?? 1) === 1; @endphp
                            <span class="px-2 py-0.5 rounded text-xs {{ $ativo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $ativo ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('planopagamento.edit', $p->id) }}"
                                   class="px-2 py-1 text-xs rounded border hover:bg-gray-50">Editar</a>
                                <form action="{{ route('planopagamento.destroy', $p->id) }}" method="POST"
                                      onsubmit="return confirm('Excluir o plano {{ $p->descricao }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-2 py-1 text-xs rounded border border-red-500 text-red-600 hover:bg-red-50">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-gray-500">Nenhum plano encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($planos, 'links'))
        <div class="mt-4">
            {{ $planos->links() }}
        </div>
    @endif
</div>
@endsection
