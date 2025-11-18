<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Inadimplência - Contas a Receber Vencidas
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-600">
                    Data base: {{ \Carbon\Carbon::parse($data_base)->format('d/m/Y') }}
                </p>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4">
                <table class="min-w-full text-xs md:text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Cliente</th>
                            <th class="text-right py-2">Total em Aberto</th>
                            <th class="text-right py-2">1-30 dias</th>
                            <th class="text-right py-2">31-60 dias</th>
                            <th class="text-right py-2">61-90 dias</th>
                            <th class="text-right py-2">&gt; 90 dias</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dados as $linha)
                            <tr class="border-b">
                                <td class="py-1">
                                    {{-- ajuste aqui se o nome vier por relação --}}
                                    {{ $linha->nome_cliente ?? ('Cliente #' . $linha->cliente_id) }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->total_em_aberto, 2, ',', '.') }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->faixa_1_30, 2, ',', '.') }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->faixa_31_60, 2, ',', '.') }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->faixa_61_90, 2, ',', '.') }}
                                </td>
                                <td class="py-1 text-right">
                                    R$ {{ number_format($linha->faixa_acima_90, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-2 text-center text-gray-500">
                                    Nenhum título em atraso até a data base.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
