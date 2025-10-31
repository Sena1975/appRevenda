<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Detalhes da Conta #{{ $conta->id }}</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p><strong>Cliente:</strong> {{ $conta->cliente_nome }}</p>
                <p><strong>Revendedora:</strong> {{ $conta->revendedora_nome }}</p>
                <p><strong>Pedido:</strong> #{{ $conta->pedido_id }}</p>
                <p><strong>Data Pedido:</strong> {{ \Carbon\Carbon::parse($conta->data_pedido)->format('d/m/Y') }}</p>
            </div>
            <div>
                <p><strong>Parcela:</strong> {{ $conta->parcela }}/{{ $conta->total_parcelas }}</p>
                <p><strong>Data Vencimento:</strong> {{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}</p>
                <p><strong>Valor:</strong> <span class="font-semibold text-blue-700">
                    R$ {{ number_format($conta->valor, 2, ',', '.') }}
                </span></p>
                <p><strong>Status:</strong>
                    @if($conta->status == 'ABERTO')
                        <span class="text-yellow-700 font-semibold">ABERTO</span>
                    @elseif($conta->status == 'PAGO')
                        <span class="text-green-700 font-semibold">PAGO</span>
                    @else
                        <span class="text-red-700 font-semibold">CANCELADO</span>
                    @endif
                </p>
            </div>
        </div>

        <div>
            <p><strong>Observação:</strong><br>{{ $conta->observacao ?? '-' }}</p>
        </div>

        <div class="flex justify-between mt-6">
            <a href="{{ route('contas.index') }}" class="bg-gray-300 px-4 py-2 rounded text-sm hover:bg-gray-400">
                Voltar
            </a>

            @if($conta->status == 'ABERTO')
                <form action="#" method="POST">
                    {{-- botão de baixa virá na próxima etapa --}}
                    <button type="button"
                        class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 cursor-not-allowed"
                        disabled>
                        Registrar Baixa (em breve)
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
