<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Estoque Consolidado</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr class="text-gray-700">
                    <th class="p-2 border text-left">Produto</th>
                    <th class="p-2 border text-center w-28">Cód. Fábrica</th>
                    <th class="p-2 border text-right w-24">Gerencial</th>
                    <th class="p-2 border text-right w-24">Reservado</th>
                    <th class="p-2 border text-right w-24">Avaria</th>
                    <th class="p-2 border text-right w-24">Disponível</th>
                    <th class="p-2 border text-right w-28">Últ. Compra</th>
                    <th class="p-2 border text-right w-28">Últ. Venda</th>
                    <th class="p-2 border text-center w-32">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($estoques as $estoque)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border">{{ $estoque->produto->nome ?? '—' }}</td>
                        <td class="p-2 border text-center">{{ $estoque->codfabnumero ?? '—' }}</td>
                        <td class="p-2 border text-right">{{ number_format($estoque->estoque_gerencial, 2, ',', '.') }}</td>
                        <td class="p-2 border text-right">{{ number_format($estoque->reservado, 2, ',', '.') }}</td>
                        <td class="p-2 border text-right text-red-600">{{ number_format($estoque->avaria, 2, ',', '.') }}</td>
                        <td class="p-2 border text-right font-semibold text-green-700">
                            {{ number_format($estoque->disponivel, 2, ',', '.') }}
                        </td>
                        <td class="p-2 border text-right">{{ number_format($estoque->ultimo_preco_compra, 2, ',', '.') }}</td>
                        <td class="p-2 border text-right">{{ number_format($estoque->ultimo_preco_venda, 2, ',', '.') }}</td>
                        <td class="p-2 border text-center">
                            <a href="{{ route('estoque.show', $estoque->id) }}" class="text-blue-600 hover:underline">Ver</a> |
                            <a href="{{ route('estoque.edit', $estoque->id) }}" class="text-indigo-600 hover:underline">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $estoques->links() }}
        </div>
    </div>
</x-app-layout>
