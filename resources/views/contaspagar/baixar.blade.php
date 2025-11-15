<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Contas a Pagar - Baixa de Parcela
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-6 space-y-4">

        {{-- Mensagens --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 text-sm px-3 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 text-red-800 text-sm px-3 py-2 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 text-sm px-3 py-2 rounded">
                <strong>Erros ao salvar:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info da conta --}}
        <div class="bg-white shadow rounded-lg p-4 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <div class="text-gray-500 text-xs">Fornecedor</div>
                    <div class="font-semibold">
                        {{ $conta->fornecedor->nomefantasia
                            ?? $conta->fornecedor->razaosocial
                            ?? $conta->fornecedor->nome
                            ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Pedido de Compra</div>
                    <div>#{{ $conta->compra->id ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Nota / Parcela</div>
                    <div>
                        Nº {{ $conta->numero_nota ?? '-' }}
                        <span class="text-xs text-gray-500">
                            ({{ $conta->parcela }}/{{ $conta->total_parcelas }})
                        </span>
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Vencimento</div>
                    <div>{{ optional($conta->data_vencimento)->format('d/m/Y') }}</div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Valor da Parcela</div>
                    <div class="font-semibold">
                        R$ {{ number_format($conta->valor, 2, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Situação</div>
                    <div>
                        @if ($conta->status === 'ABERTO')
                            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">ABERTO</span>
                        @elseif ($conta->status === 'PAGO')
                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">PAGO</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">CANCELADO</span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Total Baixado</div>
                    <div>R$ {{ number_format($totalBaixado, 2, ',', '.') }}</div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Saldo em Aberto</div>
                    <div class="{{ $saldo > 0 ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold' }}">
                        R$ {{ number_format($saldo, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulário de baixa --}}
        @if ($conta->status !== 'CANCELADO' && $saldo > 0)
            <div class="bg-white shadow rounded-lg p-4 text-sm">
                <h3 class="font-semibold mb-3">Registrar baixa (pagamento)</h3>

                <form method="POST" action="{{ route('contaspagar.baixar', $conta->id) }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 text-xs mb-1">Data da Baixa</label>
                            <input type="date"
                                   name="data_baixa"
                                   value="{{ old('data_baixa', now()->toDateString()) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                                   required>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-xs mb-1">Valor da Baixa</label>
                            <input type="number"
                                   name="valor_baixado"
                                   min="0.01"
                                   step="0.01"
                                   value="{{ old('valor_baixado', number_format($saldo, 2, '.', '')) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm text-right"
                                   required>
                            <span class="text-xs text-gray-500">
                                Saldo: R$ {{ number_format($saldo, 2, ',', '.') }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-xs mb-1">Forma de Pagamento</label>
                            <input type="text"
                                   name="forma_pagamento"
                                   value="{{ old('forma_pagamento', 'PIX') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                                   required>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-gray-700 text-xs mb-1">Observação (opcional)</label>
                            <textarea name="observacao"
                                      rows="2"
                                      class="w-full border-gray-300 rounded-md shadow-sm text-sm">{{ old('observacao') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between">
                        <a href="{{ route('contaspagar.edit', $conta->id) }}"
                           class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                            Ir para Edição
                        </a>

                        <div class="flex gap-2">
                            <a href="{{ route('contaspagar.index') }}"
                               class="px-3 py-1 bg-gray-200 text-gray-800 rounded text-xs">
                                Voltar
                            </a>

                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded text-xs">
                                Registrar baixa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        {{-- Histórico de baixas --}}
        <div class="bg-white shadow rounded-lg p-4 text-sm">
            <h3 class="font-semibold mb-3">Histórico de baixas</h3>

            @if ($baixas->isEmpty())
                <p class="text-gray-500 text-sm">Nenhuma baixa registrada ainda.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Data</th>
                            <th class="px-3 py-2 text-right">Valor</th>
                            <th class="px-3 py-2 text-left">Forma</th>
                            <th class="px-3 py-2 text-left">Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($baixas as $baixa)
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    {{ optional($baixa->data_baixa)->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    R$ {{ number_format($baixa->valor_baixado, 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $baixa->forma_pagamento }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $baixa->observacao }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
