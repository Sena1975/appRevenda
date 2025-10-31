<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Movimentações de Estoque</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        <div class="flex justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-700">Histórico</h3>
            <a href="{{ route('movestoque.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Nova Movimentação
            </a>
        </div>

        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border text-left">Produto</th>
                    <th class="p-2 border text-center">Tipo</th>
                    <th class="p-2 border text-right">Qtd</th>
                    <th class="p-2 border text-right">Preço</th>
                    <th class="p-2 border text-center">Origem</th>
                    <th class="p-2 border text-center">Data</th>
                    <th class="p-2 border text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movs as $mov)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border">{{ $mov->produto->nome ?? '—' }}</td>
                        <td class="p-2 border text-center">{{ $mov->tipo_mov }}</td>
                        <td class="p-2 border text-right">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                        <td class="p-2 border text-right">{{ number_format($mov->preco_unitario, 2, ',', '.') }}</td>
                        <td class="p-2 border text-center">{{ $mov->origem ?? 'Manual' }}</td>
                        <td class="p-2 border text-center">{{ \Carbon\Carbon::parse($mov->data_mov)->format('d/m/Y H:i') }}</td>
                        <td class="p-2 border text-center">
                            <span class="px-2 py-1 rounded text-xs 
                                {{ $mov->status == 'CONFIRMADO' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $mov->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $movs->links() }}
        </div>
    </div>
</x-app-layout>
