<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Formas de Pagamento</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Formas de Pagamento</h3>
            <a href="{{ route('formapagamento.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Nova Forma
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full divide-y divide-gray-200 text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left">Nome</th>
                    <th class="px-3 py-2 text-center">Gera Receber</th>
                    <th class="px-3 py-2 text-center">Máx. Parcelas</th>
                    <th class="px-3 py-2 text-center">Ativo</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($formas as $forma)
                    <tr>
                        <td class="px-3 py-2">{{ $forma->nome }}</td>
                        <td class="px-3 py-2 text-center">{{ $forma->gera_receber ? 'Sim' : 'Não' }}</td>
                        <td class="px-3 py-2 text-center">{{ $forma->max_parcelas }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="px-2 py-1 rounded text-xs {{ $forma->ativo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $forma->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right space-x-2">
                            <a href="{{ route('formapagamento.edit', $forma->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('formapagamento.destroy', $forma->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Excluir esta forma?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $formas->links() }}
        </div>
    </div>
</x-app-layout>
