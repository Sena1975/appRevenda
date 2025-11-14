<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Editar Pedido de Compra
            <span class="text-blue-600 ml-2">#{{ $numeroPedido ?? $pedido->numpedcompra }}</span>
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <form action="{{ route('compras.update', $pedido->id) }}" method="POST" id="formCompraEdit">
            @csrf
            @method('PUT')

            {{-- Cabeçalho --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <input type="text" value="{{ $pedido->fornecedor->nomefantasia }}"
                        class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nº Pedido</label>
                    <input type="text" name="numpedcompra" value="{{ $pedido->numpedcompra }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nota Fiscal</label>
                    <input type="text" name="numero_nota" value="{{ $pedido->numero_nota }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>


            {{-- Itens --}}
            <table class="min-w-full text-sm border border-gray-200" id="tabela-itens">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Produto</th>
                        <th class="p-2 border w-20 text-center">Qtde</th>
                        <th class="p-2 border w-28 text-right">Preço Compra</th>
                        <th class="p-2 border w-28 text-right">Preço Venda</th>
                        <th class="p-2 border w-24 text-center">Pontos</th>
                        <th class="p-2 border w-28 text-right">Total Compra</th>
                        <th class="p-2 border w-28 text-right">Total Venda</th>
                        <th class="p-2 border w-12"></th>
                    </tr>
                </thead>
                <tbody id="tbody-itens">
                    @foreach ($pedido->itens as $index => $item)
                        <tr>
                            <td class="border p-2">
                                <select name="itens[{{ $index }}][produto_id]"
                                    class="w-full border-gray-300 rounded-md shadow-sm produtoSelect">
                                    @foreach ($produtos as $produto)
                                        <option value="{{ $produto->id }}"
                                            {{ $produto->id == $item->produto_id ? 'selected' : '' }}>
                                            {{ $produto->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="border p-2 text-center">
                                <input type="number" step="1" min="0"
                                    name="itens[{{ $index }}][quantidade]" value="{{ $item->quantidade }}"
                                    class="w-full text-center border-gray-300 rounded-md shadow-sm quantidade">
                            </td>

                            <td class="border p-2 text-right">
                                <input type="number" step="0.01" min="0"
                                    name="itens[{{ $index }}][preco_unitario]"
                                    value="{{ number_format($item->preco_unitario, 2, '.', '') }}"
                                    class="w-full text-right border-gray-300 rounded-md shadow-sm preco-compra">
                            </td>

                            <td class="border p-2 text-right">
                                <input type="number" step="0.01" min="0"
                                    name="itens[{{ $index }}][preco_venda_unitario]"
                                    value="{{ number_format($item->preco_venda_unitario ?? 0, 2, '.', '') }}"
                                    class="w-full text-right border-gray-300 rounded-md shadow-sm preco-venda">
                            </td>

                            <td class="border p-2 text-center">
                                <input type="number" step="1" min="0"
                                    name="itens[{{ $index }}][pontos]" value="{{ $item->pontos ?? 0 }}"
                                    class="w-full text-center border-gray-300 rounded-md shadow-sm pontos">
                            </td>

                            <td class="border p-2 text-right totalCompra">
                                {{ number_format($item->quantidade * $item->preco_unitario, 2, ',', '.') }}
                            </td>
                            <td class="border p-2 text-right totalVenda">
                                {{ number_format($item->quantidade * ($item->preco_venda_unitario ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="border p-2 text-center">
                                <button type="button" class="text-red-600 removerItem">✖</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totais --}}
            <div class="flex justify-end items-center mt-6 space-x-8">
                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Compra:</label>
                    <input type="text" id="valor_total_display"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="valor_total" name="valor_total">
                </div>
                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Venda:</label>
                    <input type="text" id="preco_venda_total_display"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="preco_venda_total" name="preco_venda_total">
                </div>
                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Pontos:</label>
                    <input type="text" id="pontos_total_display"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="pontos_total" name="pontos_total">
                </div>
            </div>

            {{-- Botões --}}
            <div class="flex justify-end mt-10 space-x-6">
                <a href="{{ route('compras.index') }}"
                    class="px-6 py-3 rounded-md shadow-md transition font-semibold text-base"
                    style="background-color: #6b7280; color: #ffffff !important; letter-spacing: 0.5px;">
                    ← Voltar
                </a>

                <button type="submit" name="acao" value="salvar"
                    class="flex items-center px-8 py-3 rounded-md shadow-md transition font-semibold text-base"
                    style="background-color: #1d4ed8; color: #ffffff !important; letter-spacing: 0.5px;">
                    💾 <span class="ml-3">Salvar</span>
                </button>

                <button type="submit" name="acao" value="confirmar"
                    class="flex items-center px-8 py-3 rounded-md shadow-md transition font-semibold text-base"
                    style="background-color: #15803d; color: #ffffff !important; letter-spacing: 0.5px;">
                    ✅ <span class="ml-3">Confirmar Recebimento</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Script de cálculo automático --}}
    <script>
        document.querySelectorAll('.quantidade, .preco-compra, .preco-venda, .pontos').forEach(el => {
            el.addEventListener('input', calcularTotais);
        });

        function calcularTotais() {
            let totalCompra = 0,
                totalVenda = 0,
                totalPontos = 0;

            document.querySelectorAll('#tbody-itens tr').forEach(linha => {
                const qtd = parseFloat(linha.querySelector('.quantidade').value) || 0;
                const precoCompra = parseFloat(linha.querySelector('.preco-compra').value) || 0;
                const precoVenda = parseFloat(linha.querySelector('.preco-venda').value) || 0;
                const pontos = parseFloat(linha.querySelector('.pontos').value) || 0;

                const totalC = qtd * precoCompra;
                const totalV = qtd * precoVenda;
                const totalP = qtd * pontos;

                linha.querySelector('.totalCompra').textContent = totalC.toFixed(2);
                linha.querySelector('.totalVenda').textContent = totalV.toFixed(2);

                totalCompra += totalC;
                totalVenda += totalV;
                totalPontos += totalP;
            });

            document.getElementById('valor_total_display').value = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total_display').value = totalVenda.toFixed(2);
            document.getElementById('pontos_total_display').value = totalPontos.toFixed(2);

            document.getElementById('valor_total').value = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total').value = totalVenda.toFixed(2);
            document.getElementById('pontos_total').value = totalPontos.toFixed(2);
        }

        // Atualiza totais ao carregar
        window.addEventListener('DOMContentLoaded', calcularTotais);
    </script>
</x-app-layout>
