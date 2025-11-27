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

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <select id="cliente_id" name="cliente_id"
                                class="w-full border-gray-300 rounded-md shadow-sm cliente-select" required>
                                <option value="">Selecione...</option>
                                @foreach ($clientes ?? [] as $cliente)
                                    @php
                                        $indicadorId = (int) ($cliente->indicador_id ?? 1);
                                        $indicadorTexto =
                                            $indicadorId === 1
                                                ? 'ID-1 (Vendedor padrão / sem indicação)'
                                                : 'ID-' . $indicadorId . ' (cliente indicado)';
                                    @endphp
                                    <option value="{{ $cliente->id }}"
                                        data-indicador-id="{{ $indicadorId }}"
                                        data-indicador-text="{{ $indicadorTexto }}"
                                        @selected(old('cliente_id') == $cliente->id)>
                                        {{ $cliente->nome ?? ($cliente->nomecompleto ?? $cliente->razaosocial) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botão Novo Cliente --}}
                        <div>
                            <a href="{{ route('clientes.create') }}" target="_blank"
                                class="px-3 py-2 bg-emerald-600 text-white text-xs rounded shadow hover:bg-emerald-700">
                                + Novo
                            </a>
                        </div>
                    </div>

                

                                        {{-- Info do indicador --}}
                <div id="indicador-wrapper"
                    class="mt-2 text-xs text-gray-600 bg-blue-50 border border-blue-100 rounded p-2">
                    <span class="font-semibold">Indicador:</span>
                    <span id="indicador-text">Selecione um cliente para ver o indicador.</span>
                </div>
                    
                </div>



                {{-- Revendedora --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Revendedora</label>
                    <select name="revendedora_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($revendedoras ?? [] as $rev)
                            <option value="{{ $rev->id }}"
                                @selected(old('revendedora_id', $revendedoraPadraoId) == $rev->id)>
                                {{ $rev->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Data Venda --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data da Venda</label>
                    <input type="date" name="data_pedido"
                        value="{{ old('data_pedido', now()->toDateString()) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                {{-- Previsão de entrega --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Previsão de Entrega</label>
                    <input type="date" name="previsao_entrega"
                        value="{{ old('previsao_entrega', now()->toDateString()) }}"
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
                    <textarea name="observacao" rows="2"
                        class="w-full border-gray-300 rounded-md shadow-sm">{{ old('observacao') }}</textarea>
                </div>
            </div>

            {{-- Importar texto de pedido (WhatsApp) --}}
            <div class="mb-6 border rounded-lg p-4 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">
                    Importar texto do pedido (WhatsApp)
                </h3>
                <p class="text-xs text-gray-500 mb-2">
                    Cole aqui a mensagem enviada pelo cliente (no padrão "1 Unidade(s) - Código: 6587 - Preço: R$ 25,90
                    - ...").
                    O sistema vai identificar os itens e preencher a tabela abaixo.
                </p>

                <textarea id="textoWhatsapp" rows="5"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm mb-3"
                    placeholder="Cole o texto do pedido aqui..."></textarea>

                <div class="flex items-center justify-between">
                    <button type="button" id="btnImportarTextoWhatsapp"
                        class="px-3 py-1 bg-indigo-600 text-white text-xs rounded shadow hover:bg-indigo-700">
                        Ler texto e importar itens
                    </button>

                    <span class="text-[11px] text-gray-500">
                        Dica: use o texto completo que a Natura envia com os itens e o total.
                    </span>
                </div>
            </div>

            {{-- Itens --}}
            <h3 class="text-lg font-semibold mb-2">Itens da Venda</h3>

            <div class="mb-2">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" id="somenteEstoque" class="rounded border-gray-300 mr-2">
                    Somente produtos com estoque disponível
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border" id="tabela-itens">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            {{-- descrição um pouco menor --}}
                            <th class="px-2 py-1 border w-1/4">Código / Descrição</th>

                            {{-- Qtd maior --}}
                            <th class="px-2 py-1 border text-right w-24">Qtd</th>

                            <th class="px-2 py-1 border text-right w-20">Pontos</th>

                            {{-- Preço compra continua oculto --}}
                            <th class="px-2 py-1 border text-right w-24 hidden">Preço Compra</th>

                            {{-- Preço venda maior --}}
                            <th class="px-2 py-1 border text-right w-32">Preço Venda</th>

                            <th class="px-2 py-1 border text-right w-24 hidden">Desconto</th>
                            <th class="px-2 py-1 border text-right w-28">Total</th>
                            <th class="px-2 py-1 border text-right w-28">Lucro</th>
                            <th class="px-2 py-1 border text-center w-20">Ações</th>
                        </tr>
                    </thead>

                    <tbody id="tbody-itens">
                        <tr class="linha-item" data-index="0">
                            {{-- Código / Descrição --}}
                            <td class="px-2 py-1 border w-1/4">
                                <select class="produto-select w-full" data-index="0" style="width: 100%;"></select>
                                <input type="hidden" name="itens[0][codfabnumero]" class="input-codfab">
                                <input type="hidden" name="itens[0][produto_id]" class="input-produto-id">
                                {{-- Preço compra fica só em hidden, para cálculo de lucro --}}
                                <input type="hidden" class="input-preco-compra">
                            </td>

                            {{-- Qtd --}}
                            <td class="px-2 py-1 border text-right w-24">
                                <input type="number" min="1" value="1"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-quantidade"
                                    name="itens[0][quantidade]">
                            </td>

                            {{-- Pontos --}}
                            <td class="px-2 py-1 border text-right w-20">
                                <input type="number"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-pontos"
                                    name="itens[0][pontuacao]">
                            </td>

                            {{-- Preço Venda --}}
                            <td class="px-2 py-1 border text-right w-32">
                                <input type="number" step="0.01" min="0"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-preco-venda"
                                    name="itens[0][preco_unitario]">
                            </td>

                            {{-- Desconto (R$ na linha) --}}
                            <td class="px-2 py-1 border text-right w-24" hidden>
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
                {{-- Lado esquerdo: adicionar e importar --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btnAddItem"
                        class="px-3 py-1 bg-blue-500 text-white text-sm rounded shadow">
                        + Adicionar item
                    </button>

                    <button type="button" id="btnImportarVenda"
                        class="px-3 py-1 bg-indigo-500 text-white text-sm rounded shadow">
                        Importar Itens (CSV)
                    </button>

                    <input type="file" id="arquivoImportacaoVenda" accept=".csv,.txt" class="hidden">

                    <span class="text-xs text-gray-500">
                        Formato padrão: CODIGO;QTD;PRECO_COMPRA;PONTOS;PRECO_VENDA
                    </span>
                </div>

                {{-- Lado direito: totais --}}
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div>Total Pontos: <span id="totalPontosSpan">0</span></div>
                    <div>Total Venda: <span id="totalVendaSpan">0,00</span></div>
                    <div class="font-semibold">
                        Lucro Total: <span id="totalLucroSpan">0,00</span>
                    </div>
                </div>
            </div>

            {{-- Hidden totais para o backend --}}
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

            // Cliente: Select2 com busca
            $('.cliente-select').select2({
                placeholder: 'Selecione ou digite o nome do cliente...',
                width: '100%'
            });

            // --------- Indicador do cliente (campanha de indicação) ---------
            const indicadorTextEl = document.getElementById('indicador-text');
            const clienteSelectEl = document.getElementById('cliente_id');

            function atualizarIndicadorSelect() {
                if (!clienteSelectEl || !indicadorTextEl) return;

                const opt = clienteSelectEl.options[clienteSelectEl.selectedIndex];
                if (!opt || !opt.dataset) {
                    indicadorTextEl.textContent = 'Selecione um cliente para ver o indicador.';
                    return;
                }

                const texto = opt.dataset.indicadorText || 'ID-1 (Vendedor padrão / sem indicação)';
                indicadorTextEl.textContent = texto;
            }

            // Usa jQuery porque o Select2 dispara change com .trigger('change')
            if (clienteSelectEl) {
                $('#cliente_id').on('change', function() {
                    atualizarIndicadorSelect();
                });

                // Se já tiver cliente selecionado (ex: após validação), atualiza ao carregar
                if (clienteSelectEl.value) {
                    atualizarIndicadorSelect();
                }
            }

            function toNumber(val) {
                return parseFloat((val || '0').toString().replace(',', '.')) || 0;
            }

            function initSelect2(row) {
                let $select = $(row).find('.produto-select');

                $select.select2({
                    placeholder: 'Buscar produto...',
                    minimumInputLength: 2,
                    width: '100%',
                    ajax: {
                        url: '{{ route('produtos.lookup') }}',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                q: params.term,
                                only_in_stock: $('#somenteEstoque').is(':checked') ? 1 : 0
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        }
                    }
                });

                $select.on('select2:select', function(e) {
                    const dados = e.params.data;

                    row.querySelector('.input-codfab').value = dados.codigo_fabrica || '';
                    row.querySelector('.input-produto-id').value = dados.produto_id || '';

                    row.querySelector('.input-preco-compra').value = (dados.preco_compra ?? 0).toFixed(2);
                    row.querySelector('.input-preco-venda').value = (dados.preco_revenda ?? 0).toFixed(2);
                    row.querySelector('.input-pontos').value = dados.pontos ?? 0;

                    recalcularLinha(row);
                    recalcularTotais();
                });

                $(row).find('.input-quantidade, .input-desconto, .input-preco-venda').on('input', function() {
                    recalcularLinha(row);
                    recalcularTotais();
                });
            }

            function recalcularLinha(row) {
                const qtd = toNumber(row.querySelector('.input-quantidade').value);
                const pCompra = toNumber(row.querySelector('.input-preco-compra').value);
                const pVenda = toNumber(row.querySelector('.input-preco-venda').value);
                const desconto = toNumber(row.querySelector('.input-desconto').value);

                const totalBruto = qtd * pVenda;
                const total = Math.max(0, totalBruto - desconto);

                const custoTotal = qtd * pCompra;
                const lucro = total - custoTotal;

                row.querySelector('.input-total-linha').value = total.toFixed(2);
                row.querySelector('.input-lucro-linha').value = lucro.toFixed(2);
            }

            function recalcularTotais() {
                let totalVenda = 0;
                let totalPontos = 0;
                let totalLucro = 0;

                document.querySelectorAll('#tbody-itens tr').forEach(function(linha) {
                    const qtd = toNumber(linha.querySelector('.input-quantidade').value);
                    const pVenda = toNumber(linha.querySelector('.input-preco-venda').value);
                    const pCompra = toNumber(linha.querySelector('.input-preco-compra').value);
                    const desconto = toNumber(linha.querySelector('.input-desconto').value);
                    const pontos = toNumber(linha.querySelector('.input-pontos').value);

                    const totalBruto = qtd * pVenda;
                    const totalLinha = Math.max(0, totalBruto - desconto);
                    const custoTotal = qtd * pCompra;
                    const lucroLinha = totalLinha - custoTotal;

                    totalVenda += totalLinha;
                    totalPontos += (qtd * pontos);
                    totalLucro += lucroLinha;
                });

                document.getElementById('totalVendaSpan').innerText = totalVenda.toFixed(2).replace('.', ',');
                document.getElementById('totalPontosSpan').innerText = totalPontos.toFixed(0);
                document.getElementById('totalLucroSpan').innerText = totalLucro.toFixed(2).replace('.', ',');

                document.getElementById('total_venda').value = totalVenda.toFixed(2);
                document.getElementById('total_pontos').value = totalPontos.toFixed(0);
                document.getElementById('total_lucro').value = totalLucro.toFixed(2);
            }

            function adicionarLinha() {
                const tbody = document.getElementById('tbody-itens');
                const modelo = tbody.querySelector('.linha-item');

                indice++;

                const novaLinha = modelo.cloneNode(true);
                novaLinha.dataset.index = indice;

                novaLinha.querySelectorAll('input').forEach(function(inp) {
                    inp.value = '';
                });
                novaLinha.querySelector('.input-quantidade').value = 1;
                novaLinha.querySelector('.input-desconto').value = 0;

                novaLinha.querySelector('.input-codfab')
                    .setAttribute('name', 'itens[' + indice + '][codfabnumero]');
                novaLinha.querySelector('.input-produto-id')
                    .setAttribute('name', 'itens[' + indice + '][produto_id]');
                novaLinha.querySelector('.input-quantidade')
                    .setAttribute('name', 'itens[' + indice + '][quantidade]');
                novaLinha.querySelector('.input-desconto')
                    .setAttribute('name', 'itens[' + indice + '][desconto]');
                novaLinha.querySelector('.input-pontos')
                    .setAttribute('name', 'itens[' + indice + '][pontuacao]');
                novaLinha.querySelector('.input-preco-venda')
                    .setAttribute('name', 'itens[' + indice + '][preco_unitario]');

                let selectTd = novaLinha.querySelector('.produto-select').parentElement;
                selectTd.innerHTML =
                    '<select class="produto-select w-full" data-index="' + indice +
                    '" style="width:100%;"></select>' +
                    '<input type="hidden" name="itens[' + indice + '][codfabnumero]" class="input-codfab">' +
                    '<input type="hidden" name="itens[' + indice + '][produto_id]" class="input-produto-id">' +
                    '<input type="hidden" class="input-preco-compra">';

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

            // "Somente produtos com estoque" → recarrega buscas
            $('#somenteEstoque').on('change', function() {
                $('.produto-select').val(null).trigger('change');
            });

            // ----------------- IMPORTAÇÃO CSV PARA VENDA -----------------
            const inputArquivoVenda = document.getElementById('arquivoImportacaoVenda');
            const btnImportarVenda = document.getElementById('btnImportarVenda');

            if (btnImportarVenda && inputArquivoVenda) {
                btnImportarVenda.addEventListener('click', function() {
                    inputArquivoVenda.click();
                });

                inputArquivoVenda.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const texto = ev.target.result;
                        const linhas = texto.split(/\r?\n/).filter(l => l.trim() !== '');

                        linhas.forEach(function(linha) {
                            const partes = linha.split(';');
                            if (partes.length < 2) return;

                            const codigo = (partes[0] || '').trim();
                            const qtd = toNumber(partes[1] || '0');

                            let pontos = 0;
                            let precoVenda = 0;

                            if (partes.length >= 5) {
                                pontos = toNumber(partes[3] || '0');
                                precoVenda = toNumber(partes[4] || '0');
                            } else if (partes.length === 4) {
                                pontos = toNumber(partes[2] || '0');
                                precoVenda = toNumber(partes[3] || '0');
                            } else if (partes.length === 3) {
                                precoVenda = toNumber(partes[2] || '0');
                            }

                            if (!codigo || qtd <= 0) return;

                            const novaLinha = adicionarLinha();
                            const $select = $(novaLinha).find('.produto-select');

                            $.ajax({
                                url: '{{ route('produtos.lookup') }}',
                                dataType: 'json',
                                data: {
                                    q: codigo
                                },
                                success: function(data) {
                                    if (!data || !data.length) {
                                        console.warn('Produto não encontrado para código', codigo);
                                        return;
                                    }

                                    let prod = data.find(p => p.codigo_fabrica == codigo) || data[0];

                                    const option = new Option(prod.text, prod.id, true, true);
                                    $select.append(option).trigger('change');

                                    $select.trigger({
                                        type: 'select2:select',
                                        params: {
                                            data: prod
                                        }
                                    });

                                    novaLinha.querySelector('.input-quantidade').value = qtd;

                                    if (!isNaN(pontos) && pontos > 0) {
                                        novaLinha.querySelector('.input-pontos').value = pontos;
                                    }

                                    if (!isNaN(precoVenda) && precoVenda > 0) {
                                        novaLinha.querySelector('.input-preco-venda').value =
                                            precoVenda.toFixed(2);
                                    }

                                    recalcularLinha(novaLinha);
                                    recalcularTotais();
                                }
                            });
                        });
                    };

                    reader.readAsText(file, 'UTF-8');
                });
            }

            // ----------------- IMPORTAÇÃO VIA TEXTO WHATSAPP -----------------
            const btnImportarTexto = document.getElementById('btnImportarTextoWhatsapp');
            const textareaTexto = document.getElementById('textoWhatsapp');

            if (btnImportarTexto && textareaTexto) {
                btnImportarTexto.addEventListener('click', function() {
                    const texto = (textareaTexto.value || '').trim();

                    if (!texto) {
                        alert('Cole o texto do pedido do WhatsApp antes de importar.');
                        return;
                    }

                    fetch('{{ route('vendas.importar.texto') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                texto: texto
                            })
                        })
                        .then(res => res.json())
                        .then(resp => {
                            if (!resp.success) {
                                alert(resp.message || 'Não foi possível interpretar o texto.');
                                return;
                            }

                            const itens = resp.itens || [];
                            if (!itens.length) {
                                alert('Nenhum item foi retornado.');
                                return;
                            }

                            // Filtra só itens com código e quantidade > 0
                            const itensValidos = itens.filter(it => {
                                const cod = (it.codigo || '').trim();
                                const qtd = parseFloat(it.quantidade || 0);
                                return cod && qtd && qtd > 0;
                            });

                            if (!itensValidos.length) {
                                alert('Nenhum item válido foi encontrado no texto.');
                                return;
                            }

                            const tbody = document.getElementById('tbody-itens');
                            let linhas = tbody.querySelectorAll('.linha-item');

                            // Garante que exista pelo menos 1 linha base
                            if (!linhas.length) {
                                adicionarLinha();
                                linhas = tbody.querySelectorAll('.linha-item');
                            }

                            const linhaBase = linhas[0];

                            // Remove todas as linhas extras (deixa só a primeira)
                            linhas.forEach((linha, idx) => {
                                if (idx > 0) linha.remove();
                            });

                            // Limpa a linha base (inputs e select2)
                            $(linhaBase).find('.produto-select').val(null).trigger('change');
                            linhaBase.querySelectorAll('input').forEach(inp => inp.value = '');
                            linhaBase.querySelector('.input-quantidade').value = 1;
                            linhaBase.querySelector('.input-desconto').value = 0;

                            // Zera totais
                            document.getElementById('totalVendaSpan').innerText = '0,00';
                            document.getElementById('totalPontosSpan').innerText = '0';
                            document.getElementById('totalLucroSpan').innerText = '0,00';
                            document.getElementById('total_venda').value = '0.00';
                            document.getElementById('total_pontos').value = '0';
                            document.getElementById('total_lucro').value = '0.00';

                            // Controle dos itens não importados
                            let totalItens = itensValidos.length;
                            let processados = 0;
                            let missingItems = [];

                            function registrarProcessado() {
                                processados++;
                                if (processados === totalItens) {
                                    // Terminamos todos os AJAX → se houver faltando, gera TXT
                                    if (missingItems.length) {
                                        let linhasTxt = [];
                                        linhasTxt.push(
                                            'Itens não importados (produto não encontrado no sistema)'
                                        );
                                        linhasTxt.push('');

                                        missingItems.forEach(mi => {
                                            const precoTxt = (!isNaN(mi.preco_unitario) &&
                                                    mi.preco_unitario > 0) ?
                                                mi.preco_unitario.toFixed(2) :
                                                '';
                                            linhasTxt.push(
                                                `Código: ${mi.codigo} | Qtde: ${mi.quantidade} | Preço: ${precoTxt} | Descrição: ${mi.descricao || ''}`
                                            );
                                        });

                                        const conteudo = linhasTxt.join('\r\n');
                                        const blob = new Blob([conteudo], {
                                            type: 'text/plain;charset=utf-8'
                                        });
                                        const url = URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        const agora = new Date();
                                        const stamp = agora.toISOString().replace(/[:\-T]/g, '').slice(
                                            0, 15);

                                        a.href = url;
                                        a.download = `itens_nao_importados_${stamp}.txt`;
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                        URL.revokeObjectURL(url);

                                        alert(
                                            `${missingItems.length} item(ns) não foram importados. Um arquivo TXT foi baixado com a lista.`
                                        );
                                    }
                                }
                            }

                            // Para cada item válido → uma linha distinta
                            itensValidos.forEach(function(item, idx) {
                                const codigo = (item.codigo || '').trim();
                                const qtd = parseFloat(item.quantidade || 0);
                                const precoV = parseFloat(item.preco_unitario || 0);

                                // 1º item reaproveita a linha base, demais criam novas linhas
                                let linhaAlvo = (idx === 0) ? linhaBase : adicionarLinha();
                                const $select = $(linhaAlvo).find('.produto-select');

                                $.ajax({
                                    url: '{{ route('produtos.lookup') }}',
                                    dataType: 'json',
                                    data: {
                                        q: codigo
                                    },
                                    success: function(data) {
                                        // Se não encontrou o produto, não mantém a linha
                                        if (!data || !data.length) {
                                            if (idx === 0) {
                                                // limpa a base
                                                $(linhaBase).find('.produto-select')
                                                    .val(null).trigger('change');
                                                linhaBase.querySelectorAll('input')
                                                    .forEach(inp => inp.value = '');
                                                linhaBase.querySelector(
                                                        '.input-quantidade').value =
                                                    1;
                                                linhaBase.querySelector(
                                                    '.input-desconto').value = 0;
                                            } else {
                                                // remove a linha criada
                                                linhaAlvo.remove();
                                            }

                                            missingItems.push({
                                                codigo: codigo,
                                                quantidade: qtd,
                                                preco_unitario: isNaN(precoV) ? 0 : precoV,
                                                descricao: item.descricao || ''
                                            });

                                            return;
                                        }

                                        let prod = data.find(p => p.codigo_fabrica == codigo) || data[0];

                                        const option = new Option(prod.text, prod.id, true, true);
                                        $select.append(option).trigger('change');

                                        // dispara o select2:select para preencher preço/pontos/custo
                                        $select.trigger({
                                            type: 'select2:select',
                                            params: {
                                                data: prod
                                            }
                                        });

                                        // aplica quantidade
                                        linhaAlvo.querySelector('.input-quantidade').value = qtd;

                                        // se veio preço no texto, sobrescreve
                                        if (!isNaN(precoV) && precoV > 0) {
                                            linhaAlvo.querySelector('.input-preco-venda').value =
                                                precoV.toFixed(2);
                                        }

                                        recalcularLinha(linhaAlvo);
                                        recalcularTotais();
                                    },
                                    error: function() {
                                        // Erro de comunicação também conta como não importado
                                        missingItems.push({
                                            codigo: codigo,
                                            quantidade: qtd,
                                            preco_unitario: isNaN(precoV) ? 0 : precoV,
                                            descricao: item.descricao || ''
                                        });

                                        if (idx > 0) {
                                            linhaAlvo.remove();
                                        }
                                    },
                                    complete: function() {
                                        registrarProcessado();
                                    }
                                });
                            });
                        })
                        .catch(err => {
                            console.error('Erro ao importar texto:', err);
                            alert('Erro ao enviar o texto para interpretação.');
                        });
                });
            }

        });

        // ------------------- Script de Planos por Forma -------------------
        (function() {
            const formaSel = document.getElementById('formaPagamento');
            const planoSel = document.getElementById('planoPagamento');
            const planoCod = document.getElementById('planoPagamentoCodigo');

            async function carregarPlanos(formaId) {
                planoSel.innerHTML = '<option value="">Carregando...</option>';
                planoSel.disabled = true;
                if (planoCod) planoCod.value = '';

                if (!formaId) {
                    planoSel.innerHTML = '<option value="">Selecione a forma primeiro...</option>';
                    return;
                }

                try {
                    const res = await fetch(`/planos-por-forma/${encodeURIComponent(formaId)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const planos = await res.json();
                    planoSel.innerHTML = '<option value="">Selecione...</option>';

                    planos.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.parcelas > 0
                            ? `${p.descricao} (${p.parcelas}x)`
                            : p.descricao;
                        opt.dataset.codigo = p.codigo ?? '';
                        opt.dataset.parcelas = p.parcelas ?? 0;
                        opt.dataset.prazo1 = p.prazo1 ?? 0;
                        opt.dataset.prazo2 = p.prazo2 ?? 0;
                        opt.dataset.prazo3 = p.prazo3 ?? 0;
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

            if (formaSel && formaSel.value) {
                carregarPlanos(formaSel.value);
            }
        })();
    </script>
</x-app-layout>
