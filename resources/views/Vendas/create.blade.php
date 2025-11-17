<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Novo Pedido de Venda
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">

        {{-- Mensagens de erro --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                <strong>Ops! Verifique os erros abaixo:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Mensagens de sucesso --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('vendas.store') }}" method="POST" id="formVenda">
            @csrf

            {{-- Cabeçalho --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                {{-- Cliente --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                    <select name="cliente_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($clientes ?? [] as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>
                                {{ $cliente->nome ?? ($cliente->nomecompleto ?? $cliente->razaosocial) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Revendedora --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Revendedora</label>
                    <select name="revendedora_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($revendedoras ?? [] as $rev)
                            <option value="{{ $rev->id }}" @selected(old('revendedora_id') == $rev->id)>
                                {{ $rev->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Data Venda --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data da Venda</label>
                    <input type="date" name="data_pedido" value="{{ old('data_pedido', now()->toDateString()) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- Previsão de entrega --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Previsão de Entrega</label>
                    <input type="date" name="data_prevista_entrega"
                        value="{{ old('data_prevista_entrega', now()->toDateString()) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                {{-- Forma de pagamento --}}
                <div>
                    <label class="block text-sm font-medium">Forma de Pagamento *</label>
                    <select id="formaPagamento" name="forma_pagamento_id" class="w-full border rounded h-10" required>
                        <option value="">Selecione...</option>
                        @foreach ($formas ?? [] as $f)
                            <option value="{{ $f->id }}"
                                {{ old('forma_pagamento_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Plano de pagamento (preenchido via AJAX conforme a forma) --}}
                <div>
                    <label class="block text-sm font-medium">Plano de Pagamento *</label>
                    <select id="planoPagamento" name="plano_pagamento_id" class="w-full border rounded h-10" required>
                        <option value="">Selecione a forma primeiro...</option>
                    </select>
                    <input type="hidden" id="planoPagamentoCodigo" name="plano_codigo">
                </div>

                {{-- Observação --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Observação</label>
                    <textarea name="observacao" rows="2" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('observacao') }}</textarea>
                </div>
            </div>

            {{-- Itens --}}
            <h3 class="text-lg font-semibold mb-2">Itens da Venda</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border" id="tabela-itens">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="px-2 py-1 border w-1/3">Código / Descrição</th>
                            <th class="px-2 py-1 border text-right w-16">Qtd</th>
                            <th class="px-2 py-1 border text-right w-20">Pontos</th>
                            <th class="px-2 py-1 border text-right w-24">Preço Compra</th>
                            <th class="px-2 py-1 border text-right w-24">Preço Venda</th>
                            <th class="px-2 py-1 border text-right w-24">Desconto</th>
                            <th class="px-2 py-1 border text-right w-28">Total</th>
                            <th class="px-2 py-1 border text-right w-28">Lucro</th>
                            <th class="px-2 py-1 border text-center w-20">Ações</th>
                        </tr>
                    </thead>

                    <tbody id="tbody-itens">
                        {{-- Linha modelo (index 0) --}}
                        <tr class="linha-item" data-index="0">
                            {{-- Código / Descrição --}}
                            <td class="px-2 py-1 border w-1/3">
                                <select class="produto-select w-full" data-index="0" style="width: 100%;"></select>
                                <input type="hidden" name="itens[0][codfabnumero]" class="input-codfab">
                                <input type="hidden" name="itens[0][produto_id]" class="input-produto-id">
                            </td>

                            {{-- Qtd --}}
                            <td class="px-2 py-1 border text-right w-16">
                                <input type="number" min="1" value="1"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-quantidade"
                                    name="itens[0][quantidade]">
                            </td>

                            {{-- Pontos --}}
                            <td class="px-2 py-1 border text-right w-20">
                                <input type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-pontos"
                                    readonly>
                            </td>

                            {{-- Preço Compra (último custo) --}}
                            <td class="px-2 py-1 border text-right w-24">
                                <input type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-preco-compra"
                                    readonly>
                            </td>

                            {{-- Preço Venda --}}
                            <td class="px-2 py-1 border text-right w-24">
                                <input type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-preco-venda"
                                    readonly>
                            </td>

                            {{-- Desconto (R$ na linha) --}}
                            <td class="px-2 py-1 border text-right w-24">
                                <input type="number" step="0.01" min="0" value="0"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-desconto"
                                    name="itens[0][desconto]">
                            </td>

                            {{-- Total --}}
                            <td class="px-2 py-1 border text-right w-28">
                                <input type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-total-linha"
                                    readonly>
                            </td>

                            {{-- Lucro --}}
                            <td class="px-2 py-1 border text-right w-28">
                                <input type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-lucro-linha"
                                    readonly>
                            </td>

                            {{-- Ações --}}
                            <td class="px-2 py-1 border text-center w-20">
                                <button type="button"
                                    class="px-2 py-1 text-xs bg-red-500 text-white rounded btn-remover-linha">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Botão + Totais --}}
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <button type="button" id="btnAddItem"
                        class="px-3 py-1 bg-blue-500 text-white text-sm rounded shadow">
                        + Adicionar item
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div>Total Pontos: <span id="totalPontosSpan">0</span></div>
                    <div>Total Venda: <span id="totalVendaSpan">0,00</span></div>
                    <div class="font-semibold">
                        Lucro Total: <span id="totalLucroSpan">0,00</span>
                    </div>
                </div>
            </div>

            {{-- Hidden totais para o backend (se quiser usar) --}}
            <input type="hidden" name="total_pontos" id="total_pontos">
            <input type="hidden" name="total_venda" id="total_venda">
            <input type="hidden" name="total_lucro" id="total_lucro">

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('vendas.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded shadow text-sm">
                    Cancelar
                </a>

            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded shadow text-sm">
                    Salvar Venda
                </button>
            </div>
        </form>
    </div>

    {{-- CSS/JS Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        window.addEventListener('load', function() {
            // ------------------- Itens da venda -------------------
            let indice = 0;

            function toNumber(val) {
                return parseFloat((val || '0').toString().replace(',', '.')) || 0;
            }

            function initSelect2(row) {
                let $select = $(row).find('.produto-select');

                $select.select2({
                    placeholder: 'Buscar produto...',
                    minimumInputLength: 2,
                    ajax: {
                        url: '{{ route('produtos.lookup') }}',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(data) {
                            return { results: data };
                        }
                    }
                });

                $select.on('select2:select', function(e) {
                    const dados = e.params.data;

                    row.querySelector('.input-codfab').value     = dados.codigo_fabrica || '';
                    row.querySelector('.input-produto-id').value = dados.produto_id     || '';

                    // Preço compra (último custo) e preço venda da view
                    row.querySelector('.input-preco-compra').value = (dados.preco_compra  ?? 0).toFixed(2);
                    row.querySelector('.input-preco-venda').value  = (dados.preco_revenda ?? 0).toFixed(2);
                    row.querySelector('.input-pontos').value       = dados.pontos ?? 0;

                    recalcularLinha(row);
                    recalcularTotais();
                });

                $(row).find('.input-quantidade, .input-desconto').on('input', function() {
                    recalcularLinha(row);
                    recalcularTotais();
                });
            }

            function recalcularLinha(row) {
                const qtd      = toNumber(row.querySelector('.input-quantidade').value);
                const pCompra  = toNumber(row.querySelector('.input-preco-compra').value);
                const pVenda   = toNumber(row.querySelector('.input-preco-venda').value);
                const desconto = toNumber(row.querySelector('.input-desconto').value);

                const totalBruto = qtd * pVenda;
                const total      = Math.max(0, totalBruto - desconto);

                const custoTotal = qtd * pCompra;
                const lucro      = total - custoTotal;

                row.querySelector('.input-total-linha').value = total.toFixed(2);
                row.querySelector('.input-lucro-linha').value = lucro.toFixed(2);
            }

            function recalcularTotais() {
                let totalVenda  = 0;
                let totalPontos = 0;
                let totalLucro  = 0;

                document.querySelectorAll('#tbody-itens tr').forEach(function(linha) {
                    const qtd      = toNumber(linha.querySelector('.input-quantidade').value);
                    const pVenda   = toNumber(linha.querySelector('.input-preco-venda').value);
                    const pCompra  = toNumber(linha.querySelector('.input-preco-compra').value);
                    const desconto = toNumber(linha.querySelector('.input-desconto').value);
                    const pontos   = toNumber(linha.querySelector('.input-pontos').value);

                    const totalBruto = qtd * pVenda;
                    const totalLinha = Math.max(0, totalBruto - desconto);
                    const custoTotal = qtd * pCompra;
                    const lucroLinha = totalLinha - custoTotal;

                    totalVenda  += totalLinha;
                    totalPontos += (qtd * pontos);
                    totalLucro  += lucroLinha;
                });

                document.getElementById('totalVendaSpan').innerText  = totalVenda.toFixed(2).replace('.', ',');
                document.getElementById('totalPontosSpan').innerText = totalPontos.toFixed(0);
                document.getElementById('totalLucroSpan').innerText  = totalLucro.toFixed(2).replace('.', ',');

                document.getElementById('total_venda').value  = totalVenda.toFixed(2);
                document.getElementById('total_pontos').value = totalPontos.toFixed(0);
                document.getElementById('total_lucro').value  = totalLucro.toFixed(2);
            }

            function adicionarLinha() {
                const tbody  = document.getElementById('tbody-itens');
                const modelo = tbody.querySelector('.linha-item');

                indice++;

                const novaLinha = modelo.cloneNode(true);
                novaLinha.dataset.index = indice;

                // limpa inputs
                novaLinha.querySelectorAll('input').forEach(function(inp) {
                    inp.value = '';
                });
                novaLinha.querySelector('.input-quantidade').value = 1;
                novaLinha.querySelector('.input-desconto').value   = 0;

                // ajusta names
                novaLinha.querySelector('.input-codfab')
                    .setAttribute('name', 'itens[' + indice + '][codfabnumero]');
                novaLinha.querySelector('.input-produto-id')
                    .setAttribute('name', 'itens[' + indice + '][produto_id]');
                novaLinha.querySelector('.input-quantidade')
                    .setAttribute('name', 'itens[' + indice + '][quantidade]');
                novaLinha.querySelector('.input-desconto')
                    .setAttribute('name', 'itens[' + indice + '][desconto]');

                // recria select
                let selectTd = novaLinha.querySelector('.produto-select').parentElement;
                selectTd.innerHTML =
                    '<select class="produto-select w-full" data-index="' + indice + '" style="width:100%;"></select>' +
                    '<input type="hidden" name="itens[' + indice + '][codfabnumero]" class="input-codfab">' +
                    '<input type="hidden" name="itens[' + indice + '][produto_id]" class="input-produto-id">';

                // botão remover
                novaLinha.querySelector('.btn-remover-linha').addEventListener('click', function() {
                    novaLinha.remove();
                    recalcularTotais();
                });

                tbody.appendChild(novaLinha);
                initSelect2(novaLinha);

                return novaLinha;
            }

            // botão adicionar item
            document.getElementById('btnAddItem').addEventListener('click', function() {
                adicionarLinha();
            });

            // primeira linha
            const linha0 = document.querySelector('.linha-item');
            initSelect2(linha0);
            linha0.querySelector('.btn-remover-linha').addEventListener('click', function() {
                const linhas = document.querySelectorAll('.linha-item');
                if (linhas.length > 1) {
                    this.closest('tr').remove();
                    recalcularTotais();
                }
            });

            // recalcula totais ao carregar
            recalcularTotais();
        });

        // ------------------- Script de Planos por Forma (fetch, usando formaPagamento/planoPagamento) -------------------
        (function() {
            const formaSel = document.getElementById('formaPagamento');
            const planoSel = document.getElementById('planoPagamento');
            const planoCod = document.getElementById('planoPagamentoCodigo');

            async function carregarPlanos(formaId) {
                // reset UI
                planoSel.innerHTML = '<option value="">Carregando...</option>';
                planoSel.disabled = true;
                if (planoCod) planoCod.value = '';

                if (!formaId) {
                    planoSel.innerHTML = '<option value="">Selecione a forma primeiro...</option>';
                    return;
                }

                try {
                    const res = await fetch(`/planos-por-forma/${encodeURIComponent(formaId)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const planos = await res.json(); // [{id, descricao, codigo, parcelas, prazo1,prazo2,prazo3}]
                    planoSel.innerHTML = '<option value="">Selecione...</option>';

                    planos.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.parcelas > 0
                            ? `${p.descricao} (${p.parcelas}x)`
                            : p.descricao;
                        opt.dataset.codigo  = p.codigo  ?? '';
                        opt.dataset.parcelas = p.parcelas ?? 0;
                        opt.dataset.prazo1   = p.prazo1   ?? 0;
                        opt.dataset.prazo2   = p.prazo2   ?? 0;
                        opt.dataset.prazo3   = p.prazo3   ?? 0;
                        planoSel.appendChild(opt);
                    });

                    if (planos.length === 0) {
                        planoSel.innerHTML = '<option value="">Nenhum plano disponível</option>';
                        planoSel.disabled = true;
                    } else {
                        planoSel.disabled = false;
                    }
                } catch (e) {
                    console.error('Erro ao carregar planos:', e);
                    planoSel.innerHTML = '<option value="">Erro ao carregar</option>';
                    planoSel.disabled = true;
                }
            }

            if (formaSel) {
                formaSel.addEventListener('change', (e) => carregarPlanos(e.target.value));
            }

            if (planoSel) {
                planoSel.addEventListener('change', (e) => {
                    const opt = e.target.options[e.target.selectedIndex];
                    if (planoCod) planoCod.value = opt ? (opt.dataset.codigo || '') : '';
                });
            }

            // edição / validação: se já tiver forma selecionada, carrega planos ao abrir
            if (formaSel && formaSel.value) {
                carregarPlanos(formaSel.value);
            }
        })();
    </script>
</x-app-layout>
