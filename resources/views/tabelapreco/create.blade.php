{{-- resources/views/tabelapreco/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Preço de Produto</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        {{-- Erros de validação --}}
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-red-800">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tabelapreco.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- PRODUTO --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produto_id" id="produto_id"
                            class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}"
                                    data-codfab="{{ $produto->codfabnumero }}"
                                    {{ (string)old('produto_id') === (string)$produto->id ? 'selected' : '' }}>
                                {{ $produto->nome }}
                                @if($produto->codfabnumero)
                                    - {{ $produto->codfabnumero }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- CÓDIGO DE FÁBRICA --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código de Fábrica</label>
                    <input type="text" name="codfab" id="codfab" value="{{ old('codfab') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm"
                           placeholder="Ex: NT-001">
                </div>

                {{-- PREÇO DE COMPRA --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Compra</label>
                    <input type="number" step="0.01" name="preco_compra" value="{{ old('preco_compra') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0,00">
                </div>

                {{-- PREÇO DE REVENDA --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Revenda</label>
                    <input type="number" step="0.01" name="preco_revenda" value="{{ old('preco_revenda') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- PONTUAÇÃO --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pontuação</label>
                    <input type="number" name="pontuacao" value="{{ old('pontuacao') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- DATA INÍCIO --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" name="data_inicio" value="{{ old('data_inicio') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- DATA FIM --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" name="data_fim" value="{{ old('data_fim') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- STATUS --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('tabelapreco.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    {{-- Script para preencher automaticamente o CODFAB ao escolher o produto --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectProduto = document.getElementById('produto_id');
            const inputCodfab   = document.getElementById('codfab');

            if (!selectProduto || !inputCodfab) return;

            function atualizarCodfab() {
                const opt = selectProduto.options[selectProduto.selectedIndex];
                if (!opt) return;

                const codfabOption = opt.getAttribute('data-codfab') || '';

                // Só sobrescreve se o campo estiver vazio
                if (!inputCodfab.value || inputCodfab.value.trim() === '') {
                    inputCodfab.value = codfabOption;
                }
            }

            // Quando trocar o produto
            selectProduto.addEventListener('change', atualizarCodfab);

            // Se já vier com produto selecionado (ex.: após erro de validação)
            if (selectProduto.value && (!inputCodfab.value || inputCodfab.value.trim() === '')) {
                atualizarCodfab();
            }
        });
    </script>
</x-app-layout>
