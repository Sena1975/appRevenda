{{-- resources/views/tabelapreco/edit.blade.php --}}
@php($registro = $tabela ?? $tabelapreco)
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Tabela de Preço</h2>
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

        <form action="{{ route('tabelapreco.update', $registro->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produto_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}"
                                {{ (string)old('produto_id', $registro->produto_id)===(string)$produto->id ? 'selected' : '' }}>
                                {{ $produto->nome }} @if($produto->codfabnumero) - {{ $produto->codfabnumero }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Código de Fábrica</label>
                    <input type="text" name="codfab" value="{{ old('codfab', $registro->codfab) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Compra</label>
                    <input type="number" step="0.01" name="preco_compra" value="{{ old('preco_compra', $registro->preco_compra) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Preço de Revenda</label>
                    <input type="number" step="0.01" name="preco_revenda" value="{{ old('preco_revenda', $registro->preco_revenda) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Pontuação</label>
                    <input type="number" name="pontuacao" value="{{ old('pontuacao', $registro->pontuacao) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" name="data_inicio" value="{{ old('data_inicio', $registro->data_inicio) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" name="data_fim" value="{{ old('data_fim', $registro->data_fim) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ (int)old('status', $registro->status)===1 ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ (int)old('status', $registro->status)===0 ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('tabelapreco.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
