<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Contas a Receber</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">{{ session('error') }}</div>
        @endif

        <table class="min-w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">Cliente</th>
                    <th class="p-2 text-left">Revendedora</th>
                    <th class="p-2 text-center">Pedido</th>
                    <th class="p-2 text-center">Parcela</th>
                    <th class="p-2 text-right">Valor</th>
                    <th class="p-2 text-center">Vencimento</th>
                    <th class="p-2 text-center">Status</th>
                    <th class="p-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contas as $conta)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2">{{ $conta->cliente_nome }}</td>
                        <td class="p-2">{{ $conta->revendedora_nome }}</td>
                        <td class="p-2 text-center">#{{ $conta->pedido_id }}</td>
                        <td class="p-2 text-center">{{ $conta->parcela }}/{{ $conta->total_parcelas }}</td>
                        <td class="p-2 text-right font-semibold">
                            R$ {{ number_format($conta->valor, 2, ',', '.') }}
                        </td>
                        <td class="p-2 text-center">
                            {{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}
                        </td>
                        <td class="p-2 text-center">
                            @if($conta->status == 'ABERTO')
                                <span class="text-yellow-700 font-semibold">ABERTO</span>
                            @elseif($conta->status == 'PAGO')
                                <span class="text-green-700 font-semibold">PAGO</span>
                            @else
                                <span class="text-red-700 font-semibold">CANCELADO</span>
                            @endif
                        </td>
                        <td class="p-2 text-center">
                            <a href="{{ route('contas.show', $conta->id) }}"
                               class="text-blue-600 hover:underline text-sm">
                               Ver Detalhes
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center p-3 text-gray-500">Nenhum título encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
