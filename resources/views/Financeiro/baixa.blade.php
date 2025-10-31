<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Registrar Baixa</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto space-y-4">
        <form action="{{ route('baixa.store', $conta->id) }}" method="POST">
            @csrf

            <div>
                <p><strong>Conta:</strong> #{{ $conta->id }}</p>
                <p><strong>Valor original:</strong> R$ {{ number_format($conta->valor, 2, ',', '.') }}</p>
                <p><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Valor Pago</label>
                    <input type="text" name="valor_baixado"
                           value="{{ number_format($conta->valor, 2, ',', '.') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
                    <input type="text" name="forma_pagamento"
                           value="PIX"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Observação</label>
                <textarea name="observacao" rows="3" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <div class="mt-6 flex justify-between">
                <a href="{{ route('contas.index') }}"
                   class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 text-sm">
                   Cancelar
                </a>

                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                    Confirmar Baixa
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
