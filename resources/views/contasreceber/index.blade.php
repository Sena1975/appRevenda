<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Contas a Receber</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        <form method="GET" class="grid grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm text-gray-700">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="ABERTO" {{ $status == 'ABERTO' ? 'selected' : '' }}>Aberto</option>
                    <option value="PAGO" {{ $status == 'PAGO' ? 'selected' : '' }}>Pago</option>
                    <option value="CANCELADO" {{ $status == 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-700">De</label>
                <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm text-gray-700">Até</label>
                <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Filtrar</button>
            </div>
        </form>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Cliente</th>
                    <th class="px-3 py-2 text-center">Parcela</th>
                    <th class="px-3 py-2 text-center">Forma</th>
                    <th class="px-3 py-2 text-center">Vencimento</th>
                    <th class="px-3 py-2 text-right">Valor</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contas as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $c->cliente->nome ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">{{ $c->parcela }}/{{ $c->total_parcelas }}</td>
                        <td class="px-3 py-2 text-center">{{ $c->forma->nome ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($c->valor, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($c->status === 'ABERTO')
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Aberto</span>
                            @elseif($c->status === 'PAGO')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Pago</span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Cancelado</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($c->status === 'ABERTO')
                                <form action="{{ route('contasreceber.update', $c->id) }}" method="POST" onsubmit="return confirm('Confirmar pagamento?')">
                                    @csrf
                                    @method('PUT')
                                    <button class="text-blue-600 hover:text-blue-800 font-medium">Registrar Pagamento</button>
                                </form>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-gray-500">Nenhuma conta encontrada</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
