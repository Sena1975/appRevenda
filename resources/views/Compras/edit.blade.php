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
            @php
                // Função pra sempre devolver no formato YYYY-MM-DD
                if (!function_exists('dateForInput')) {
                    function dateForInput($value)
                    {
                        if (!$value) {
                            return '';
                        }

                        if ($value instanceof \Carbon\CarbonInterface) {
                            return $value->format('Y-m-d');
                        }

                        $str = (string) $value;

                        // Se já vier como 2025-11-19 ou 2025-11-19 00:00:00
                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) {
                            return substr($str, 0, 10);
                        }

                        try {
                            return \Carbon\Carbon::parse($str)->format('Y-m-d');
                        } catch (\Exception $e) {
                            return '';
                        }
                    }
                }

                // DATA DO PEDIDO  (nome alinhado com o controller: data_pedido)
                $dataPedido = old(
                    'data_pedido',
                    dateForInput($pedido->data_compra ?? ($pedido->datapedido ?? ($pedido->dt_pedido ?? null))),
                );

                // DATA DE ENTREGA / PREVISÃO (nome alinhado com o controller: data_entrega)
                $dataEntrega = old(
                    'data_entrega',
                    dateForInput(
                        $pedido->data_emissao ??
                            ($pedido->data_previsao ??
                                ($pedido->data_previsao_entrega ?? ($pedido->data_prevista_entrega ?? null))),
                    ),
                );

                // Totais vindos do banco para exibição inicial
                $totalCompraInicial = $pedido->valorcusto
                    ?? $pedido->itens->sum('valorcusto')
                    ?? $pedido->itens->sum('total_liquido');

                $totalVendaInicial = $pedido->preco_venda_total
                    ?? $pedido->itens->sum('preco_venda_total');

                $totalPontosInicial = $pedido->pontostotal
                    ?? $pedido->itens->sum('pontostotal');

                $totalLiquidoInicial = ($totalCompraInicial ?? 0) + ($pedido->encargos ?? 0);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Fornecedor --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" @selected(old('fornecedor_id', $pedido->fornecedor_id) == $fornecedor->id)>
                                {{ $fornecedor->razaosocial ?? ($fornecedor->nomefantasia ?? $fornecedor->nome) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Data do Pedido (name = data_pedido) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do Pedido</label>
                    <input type="date" name="data_pedido" value="{{ $dataPedido }}"
                        class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- Data de Entrega / Previsão (name = data_entrega) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Entrega (previsão)</label>
                    <input type="date" name="data_entrega" value="{{ $dataEntrega }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Nº Pedido --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número do Pedido</label>
                    <input type="text" name="numpedcompra" value="{{ old('numpedcompra', $pedido->numpedcompra) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Nota Fiscal --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nota Fiscal</label>
                    <input type="text" name="numero_nota" value="{{ old('numero_nota', $pedido->numero_nota) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Observação --}}
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700">Observação</label>
                    <textarea name="observacao" rows="2" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('observacao', $pedido->observacao) }}</textarea>
                </div>
            </div>

            {{-- Condições de Pagamento --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                {{-- Forma de Pagamento --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
                    <select name="forma_pagamento_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" @selected(old('forma_pagamento_id', $pedido->forma_pagamento_id ?? null) == $forma->id)>
                                {{ $forma->descricao ?? ($forma->nome ?? 'ID ' . $forma->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Plano de Pagamento --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plano de Pagamento</label>
                    <select name="plano_pagamento_id" id="plano_pagamento_id"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($planosPagamento as $plano)
                            <option value="{{ $plano->id }}" data-parcelas="{{ $plano->parcelas ?? 1 }}"
                                @selected(old('plano_pagamento_id', $pedido->plano_pagamento_id ?? null) == $plano->id)>
                                {{ $plano->descricao ?? ($plano->nome ?? 'ID ' . $plano->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Qtde de Parcelas --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Qtde de Parcelas</label>
                    <input type="number" name="qt_parcelas" min="1"
                        value="{{ old('qt_parcelas', $pedido->qt_parcelas ?? 1) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Encargos financeiros --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Encargos financeiros (R$)
                    </label>

                    <div class="text-right">
                        <label class="font-semibold text-gray-700 block">Encargos (R$)</label>
                        <input type="text" name="encargos" id="encargos"
                            value="{{ old('encargos', number_format($pedido->encargos ?? 0, 2, ',', '.')) }}"
                            class="w-32 text-right border-gray-300 rounded-md shadow-sm">
                    </div>

                    <p class="text-xs text-gray-500 mt-1">
                        Juros / taxas deste pedido. Será rateado proporcionalmente nos itens.
                    </p>
                </div>
            </div>

            {{-- Itens --}}
            <table class="min-w-full text-sm border border-gray-200" id="tabela-itens">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Produto</th>
                        <th class="p-2 border w-20 text-center">Qtde</th>
                        <th class="p-2 border w-20 text-center">Tipo</th>
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
                            <input type="hidden" name="itens[{{ $idx }}][produto_id]"
                                value="{{ $item->produto_id }}">

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
                                <input type="text" name="itens[{{ $idx }}][quantidade]"
                                    value="{{ number_format($item->quantidade, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right quantidade">
                            </td>

                            {{-- Tipo (N/B) --}}
                            <td class="p-2 border text-center">
                                @php
                                    $tipoItem = $item->tipo_item ?? 'N';
                                @endphp
                                <select name="itens[{{ $idx }}][tipo_item]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-xs">
                                    <option value="N" {{ $tipoItem === 'N' ? 'selected' : '' }}>Normal</option>
                                    <option value="B" {{ $tipoItem === 'B' ? 'selected' : '' }}>Bonificado
                                    </option>
                                </select>
                            </td>

                            {{-- Pontos --}}
                            <td class="p-2 border text-center">
                                <input type="text" name="itens[{{ $idx }}][pontos]"
                                    value="{{ number_format($item->pontos, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right pontos">
                            </td>

                            {{-- Preço Compra --}}
                            <td class="p-2 border text-right">
                                <input type="text" name="itens[{{ $idx }}][preco_compra]"
                                    value="{{ number_format($item->preco_unitario, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right preco-compra">
                            </td>

                            {{-- Desconto --}}
                            <td class="p-2 border text-right">
                                <input type="text" name="itens[{{ $idx }}][desconto]"
                                    value="{{ number_format($item->valor_desconto ?? 0, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right desconto">
                            </td>

                            {{-- Preço Venda --}}
                            <td class="p-2 border text-right">
                                <input type="text" name="itens[{{ $idx }}][preco_revenda]"
                                    value="{{ number_format($item->preco_venda_unitario, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right preco-venda">
                            </td>

                            {{-- Total Compra (custo da linha, sem encargos) --}}
                            <td class="p-2 border text-right">
                                <input type="text" name="itens[{{ $idx }}][total_custo]"
                                    value="{{ number_format($item->valorcusto ?? $item->total_liquido ?? $item->total_item, 2, ',', '') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right total-compra"
                                    readonly>
                            </td>

                            {{-- Total Venda --}}
                            <td class="p-2 border text-right">
                                <input type="text" name="itens[{{ $idx }}][total_revenda]"
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
            <div class="flex flex-wrap justify-end items-center mt-6 gap-6">
                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Compra (sem encargos):</label>
                    <input type="text" id="valor_total_display"
                        value="{{ number_format($totalCompraInicial ?? 0, 2, ',', '.') }}"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="valor_total" name="valor_total"
                        value="{{ number_format($totalCompraInicial ?? 0, 2, '.', '') }}">
                </div>

                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Venda:</label>
                    <input type="text" id="preco_venda_total_display"
                        value="{{ number_format($totalVendaInicial ?? 0, 2, ',', '.') }}"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="preco_venda_total" name="preco_venda_total"
                        value="{{ number_format($totalVendaInicial ?? 0, 2, '.', '') }}">
                </div>

                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Pontos:</label>
                    <input type="text" id="pontos_total_display"
                        value="{{ number_format($totalPontosInicial ?? 0, 2, ',', '.') }}"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="pontos_total" name="pontos_total"
                        value="{{ number_format($totalPontosInicial ?? 0, 2, '.', '') }}">
                </div>

                <div class="text-right">
                    <label class="font-semibold text-gray-700 block">Total Líquido (c/ encargos):</label>
                    <input type="text" id="total_liquido_display"
                        value="{{ number_format($totalLiquidoInicial ?? 0, 2, ',', '.') }}"
                        class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
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
                const qtdEl = linha.querySelector('.quantidade');
                const pontosEl = linha.querySelector('.pontos');
                const precoCompEl = linha.querySelector('.preco-compra');
                const precoVendEl = linha.querySelector('.preco-venda');
                const descEl = linha.querySelector('.desconto');
                const totalCompEl = linha.querySelector('.total-compra');
                const totalVendEl = linha.querySelector('.total-venda');

                const qtd = parseBrFloat(qtdEl?.value || '0');
                const pontos = parseBrFloat(pontosEl?.value || '0');
                const precoCompra = parseBrFloat(precoCompEl?.value || '0');
                const precoVenda = parseBrFloat(precoVendEl?.value || '0');
                const desconto = parseBrFloat(descEl?.value || '0');

                // mesmo critério do controller: custo sem encargos = bruto - desconto
                const totalC = Math.max(0, qtd * precoCompra - desconto);
                const totalV = qtd * precoVenda;
                const totalP = qtd * pontos;

                if (totalCompEl) totalCompEl.value = formatBr(totalC);
                if (totalVendEl) totalVendEl.value = formatBr(totalV);

                totalCompra += totalC;
                totalVenda += totalV;
                totalPontos += totalP;
            });

            const encargos = parseBrFloat(document.getElementById('encargos')?.value || '0');
            const totalLiquido = totalCompra + encargos;

            document.getElementById('valor_total_display').value = formatBr(totalCompra);
            document.getElementById('preco_venda_total_display').value = formatBr(totalVenda);
            document.getElementById('pontos_total_display').value = formatBr(totalPontos);
            document.getElementById('total_liquido_display').value = formatBr(totalLiquido);

            document.getElementById('valor_total').value = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total').value = totalVenda.toFixed(2);
            document.getElementById('pontos_total').value = totalPontos.toFixed(2);
        }

        // recalcula apenas quando o usuário mexer nos itens / encargos
        document.addEventListener('input', function(e) {
            if (
                e.target.closest('#tbody-itens') &&
                (e.target.classList.contains('quantidade') ||
                    e.target.classList.contains('preco-compra') ||
                    e.target.classList.contains('preco-venda') ||
                    e.target.classList.contains('pontos') ||
                    e.target.classList.contains('desconto'))
            ) {
                calcularTotais();
            }

            if (e.target.id === 'encargos') {
                calcularTotais();
            }
        });

        // Preenche Qtde de Parcelas ao selecionar o Plano de Pagamento
        const planoSelect = document.querySelector('select[name="plano_pagamento_id"]');
        const parcelasInput = document.querySelector('input[name="qt_parcelas"]');

        if (planoSelect && parcelasInput) {
            planoSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const parcelas = opt ? opt.getAttribute('data-parcelas') : null;

                if (parcelas) {
                    parcelasInput.value = parcelas;
                }
            });

            // se já veio um plano selecionado e qt_parcelas vazio, aplica o padrão do plano
            if (planoSelect.value && !parcelasInput.value) {
                const opt = planoSelect.options[planoSelect.selectedIndex];
                const parcelas = opt ? opt.getAttribute('data-parcelas') : null;
                if (parcelas) {
                    parcelasInput.value = parcelas;
                }
            }
        }
    </script>
</x-app-layout>
