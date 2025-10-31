<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Planos de Pagamento</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Planos</h3>
            <a href="{{ route('planopagamento.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Novo Plano</a>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-3 py-2 text-left">Código</th>
                    <th class="px-3 py-2 text-left">Descrição</th>
                    <th class="px-3 py-2 text-left">Forma</th>
                    <th class="px-3 py-2 text-center">Parcelas</th>
                    <th class="px-3 py-2 text-center">Prazo Médio</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($planos as $p)
                    <tr>
                        <td class="px-3 py-2">{{ $p->codplano }}</td>
                        <td class="px-3 py-2">{{ $p->descricao }}</td>
                        <td class="px-3 py-2">{{ $p->formaPagamento->nome ?? '-' }}</td>
                        <td class="px-3 py-2 text-center">{{ $p->parcelas }}</td>
                        <td class="px-3 py-2 text-center">{{ $p->prazomedio }}</td>
                        <td class="px-3 py-2 text-center">
                            @if ($p->ativo)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Ativo</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Inativo</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('planopagamento.edit', $p->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('planopagamento.destroy', $p->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Confirma exclusão?')" class="text-red-600 hover:underline ml-2">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $planos->links() }}
        </div>
    </div>
</x-app-layout>
