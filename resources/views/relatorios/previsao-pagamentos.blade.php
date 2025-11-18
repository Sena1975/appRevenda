<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Previsão de Pagamentos (Contas a Pagar)
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            {{-- Filtros --}}
            <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('relatorios.pagamentos.previsao') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Início</label>
                        <input type="date" name="inicio" value="{{ $inicio }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fim</label>
                        <input type="date" name="fim" value="{{ $fim }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            {{-- Tabela --}}
            <div class="bg-white shadow-sm rounded-lg p-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Data de Vencimento</th>
                            <th class="text-right py-2">Total em Aberto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalGeral = 0; @endphp

                        @forelse ($dados as $linha)
                            @php $totalGeral += $linha->total; @endphp
                            <tr class="border-b">
                                <td class="py-1">
                                    {{ \Carbon\Carbon::parse($linha->data_vencimento)->format('d/m/Y') }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->total, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 text-center text-gray-500">
                                    Nenhum título encontrado no período informado.
                                </td>
                            </tr>
                        @endforelse

                        @if($dados->count() > 0)
                            <tr>
                                <td class="py-2 font-semibold text-right">Total Geral:</td>
                                <td class="py-2 text-right font-semibold">
                                    R$ {{ number_format($totalGeral, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
