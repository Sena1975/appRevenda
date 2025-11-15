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
                    <input type="text"
                           value="{{ $pedido->fornecedor->nomefantasia }}"
                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                           readonly>
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
                    <th class="p-2 border w-24 text-center">Pontos</th>
                    <th class="p-2 border w-28 text-right">Preço Compra</th>
                    <th class="p-2 border w-28 text-right">Desconto</th>
                    <th class="p-2 border w-28 text-right">Preço Venda</th>
                    <th class="p-2 border w-28 text-right">Total Compra</th>
                    <th class="p-2 border w-28 text-right">Total Venda</th>
                    <th class="p-2 border w-12"></th>
                </tr>
                </thead>
                <tbody id="tbody-itens">
                @foreach ($pedido->itens as $idx => $item)
                    <tr>
                        {{-- id do item (necessário pro update) --}}
                        <input type="hidden" name="itens[{{ $idx }}][id]" value="{{ $item->id }}">
                        <input type="hidden" name="itens[{{ $idx }}][produto_id]" value="{{ $item->produto_id }}">

                        {{-- Produto (apenas exibição) --}}
                        <td class="p-2 border">
                            <select class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" disabled>
                                <option value="{{ $item->produto_id }}">
                                    {{ $item->produto->codfabnumero ?? '' }} - {{ $item->produto->nome ?? '' }}
                                </option>
                            </select>
                        </td>

                        {{-- Quantidade --}}
                        <td class="p-2 border text-center">
                            <input type="text"
                                   name="itens[{{ $idx }}][quantidade]"
                                   value="{{ number_format($item->quantidade, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right quantidade">
                        </td>

                        {{-- Pontos --}}
                        <td class="p-2 border text-center">
                            <input type="text"
                                   name="itens[{{ $idx }}][pontos]"
                                   value="{{ number_format($item->pontos, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right pontos">
                        </td>

                        {{-- Preço Compra --}}
                        <td class="p-2 border text-right">
                            <input type="text"
                                   name="itens[{{ $idx }}][preco_compra]"
                                   value="{{ number_format($item->preco_unitario, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right preco-compra">
                        </td>

                        {{-- Desconto --}}
                        <td class="p-2 border text-right">
                            <input type="text"
                                   name="itens[{{ $idx }}][desconto]"
                                   value="{{ number_format($item->valor_desconto ?? 0, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right desconto">
                        </td>

                        {{-- Preço Venda --}}
                        <td class="p-2 border text-right">
                            <input type="text"
                                   name="itens[{{ $idx }}][preco_revenda]"
                                   value="{{ number_format($item->preco_venda_unitario, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right preco-venda">
                        </td>

                        {{-- Total Compra --}}
                        <td class="p-2 border text-right">
                            <input type="text"
                                   name="itens[{{ $idx }}][total_custo]"
                                   value="{{ number_format($item->total_item, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right total-compra"
                                   readonly>
                        </td>

                        {{-- Total Venda --}}
                        <td class="p-2 border text-right">
                            <input type="text"
                                   name="itens[{{ $idx }}][total_revenda]"
                                   value="{{ number_format($item->preco_venda_total, 2, ',', '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-right total-venda"
                                   readonly>
                        </td>

                        <td class="p-2 border text-center">
                            {{-- se quiser futuramente um botão para remover item, entra aqui --}}
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
        function parseBrFloat(v) {
            if (!v) return 0;
            v = v.replace(/\./g, '').replace(',', '.');
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function formatBr(v) {
            return v.toFixed(2).replace('.', ',');
        }

        function calcularTotais() {
            let totalCompra = 0,
                totalVenda = 0,
                totalPontos = 0;

            document.querySelectorAll('#tbody-itens tr').forEach(linha => {
                const qtdEl        = linha.querySelector('.quantidade');
                const pontosEl     = linha.querySelector('.pontos');
                const precoCompEl  = linha.querySelector('.preco-compra');
                const precoVendEl  = linha.querySelector('.preco-venda');
                const descEl       = linha.querySelector('.desconto');
                const totalCompEl  = linha.querySelector('.total-compra');
                const totalVendEl  = linha.querySelector('.total-venda');

                const qtd          = parseBrFloat(qtdEl?.value || '0');
                const pontos       = parseBrFloat(pontosEl?.value || '0');
                const precoCompra  = parseBrFloat(precoCompEl?.value || '0');
                const precoVenda   = parseBrFloat(precoVendEl?.value || '0');
                const desconto     = parseBrFloat(descEl?.value || '0');

                const totalC       = Math.max(0, qtd * precoCompra - desconto);
                const totalV       = qtd * precoVenda;
                const totalP       = qtd * pontos;

                if (totalCompEl) totalCompEl.value = formatBr(totalC);
                if (totalVendEl) totalVendEl.value = formatBr(totalV);

                totalCompra += totalC;
                totalVenda += totalV;
                totalPontos += totalP;
            });

            document.getElementById('valor_total_display').value        = formatBr(totalCompra);
            document.getElementById('preco_venda_total_display').value  = formatBr(totalVenda);
            document.getElementById('pontos_total_display').value       = formatBr(totalPontos);

            document.getElementById('valor_total').value        = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total').value  = totalVenda.toFixed(2);
            document.getElementById('pontos_total').value       = totalPontos.toFixed(2);
        }

        // adiciona listeners
        document.addEventListener('input', function (e) {
            if (e.target.closest('#tbody-itens')) {
                if (e.target.classList.contains('quantidade') ||
                    e.target.classList.contains('preco-compra') ||
                    e.target.classList.contains('preco-venda') ||
                    e.target.classList.contains('pontos') ||
                    e.target.classList.contains('desconto')) {
                    calcularTotais();
                }
            }
        });

        window.addEventListener('DOMContentLoaded', calcularTotais);
    </script>
</x-app-layout>
