<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Contas a Pagar - Editar Conta
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6 space-y-4">

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
                    <div class="text-gray-500 text-xs">Parcela</div>
                    <div>
                        {{ $conta->parcela }}/{{ $conta->total_parcelas }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 text-xs">Valor</div>
                    <div class="font-semibold">
                        R$ {{ number_format($conta->valor, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulário de edição --}}
        <div class="bg-white shadow rounded-lg p-4 text-sm">
            <h3 class="font-semibold mb-3">Editar dados da conta</h3>

            <form method="POST" action="{{ route('contaspagar.update', $conta->id) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 text-xs mb-1">Data de Vencimento</label>
                        <input type="date"
                               name="data_vencimento"
                               value="{{ old('data_vencimento', optional($conta->data_vencimento)->toDateString()) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                               required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-xs mb-1">Número da Nota</label>
                        <input type="text"
                               name="numero_nota"
                               value="{{ old('numero_nota', $conta->numero_nota) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-xs mb-1">Observação</label>
                        <textarea name="observacao"
                                  rows="2"
                                  class="w-full border-gray-300 rounded-md shadow-sm text-sm">{{ old('observacao', $conta->observacao) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <a href="{{ route('contaspagar.index') }}"
                       class="px-3 py-1 bg-gray-200 text-gray-800 rounded text-xs">
                        Voltar
                    </a>

                    <a href="{{ route('contaspagar.formBaixa', $conta->id) }}"
                       class="px-3 py-1 bg-green-600 text-white rounded text-xs">
                        Ir para Baixa
                    </a>

                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded text-xs">
                        Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
