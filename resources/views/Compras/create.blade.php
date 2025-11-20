<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Novo Pedido de Compra
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

        <form action="{{ route('compras.store') }}" method="POST" id="formCompra">
            @csrf

            {{-- Cabeçalho --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" @selected(old('fornecedor_id') == $fornecedor->id)>
                                {{ $fornecedor->razaosocial ?? ($fornecedor->nomefantasia ?? $fornecedor->nome) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do Pedido</label>
                    <input type="date" name="data_pedido" value="{{ old('data_pedido', now()->toDateString()) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Observação</label>
                    <textarea name="observacao" rows="2" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('observacao') }}</textarea>
                </div>
            </div>

            {{-- Condições de Pagamento --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
                    <select name="forma_pagamento_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" @selected(old('forma_pagamento_id') == $forma->id)>
                                {{ $forma->descricao ?? ($forma->nome ?? 'ID ' . $forma->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Plano de Pagamento</label>
                    <select name="plano_pagamento_id" id="plano_pagamento_id"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione...</option>
                        @foreach ($planosPagamento as $plano)
                            <option value="{{ $plano->id }}" data-parcelas="{{ $plano->parcelas ?? 1 }}"
                                @selected(old('plano_pagamento_id') == $plano->id)>
                                {{ $plano->descricao ?? ($plano->nome ?? 'ID ' . $plano->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">Qtde de Parcelas</label>
                    <input type="number" name="qt_parcelas" min="1" value="{{ old('qt_parcelas', 1) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            {{-- Itens --}}
            <h3 class="text-lg font-semibold mb-2">Itens do Pedido</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border" id="tabela-itens">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="px-2 py-1 border w-1/6">Código / Descrição</th>
                            <th class="px-2 py-1 border text-right w-16">Qtd</th>
                            <th class="px-2 py-1 border text-right w-20">Pontos</th>
                            <th class="px-2 py-1 border text-right w-24">Preço Compra</th>
                            <th class="px-2 py-1 border text-right w-24" hidden>Desconto</th>
                            <th class="px-2 py-1 border text-right w-24">Preço Revenda</th>
                            <th class="px-2 py-1 border text-right w-28">Total Custo</th>
                            <th class="px-2 py-1 border text-right w-28" hidden>Total Revenda</th>
                            <th class="px-2 py-1 border text-right w-24">Lucro</th>
                            <th class="px-2 py-1 border text-center w-20">Ações</th>
                        </tr>
                    </thead>

                    <tbody id="tbody-itens">
                        {{-- Linha modelo (index 0) --}}
                        <tr class="linha-item" data-index="0">
                            {{-- Código / Descrição --}}
                            <td class="px-2 py-1 border w-1/6">
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
                                <input type="text" name="itens[0][pontos]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-pontos"
                                    readonly>
                            </td>

                            {{-- Preço Compra --}}
                            <td class="px-2 py-1 border text-right w-24">
                                <input type="text" name="itens[0][preco_compra]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-preco-compra"
                                    readonly>
                            </td>

                            {{-- Desconto (por item, R$) --}}
                            <td class="px-2 py-1 border text-right w-24" hidden>
                                <input type="number" step="0.01" min="0" value="0"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-desconto"
                                    name="itens[0][desconto]">
                            </td>

                            {{-- Preço Revenda --}}
                            <td class="px-2 py-1 border text-right w-24" hidden>
                                <input type="text" name="itens[0][preco_revenda]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-preco-revenda"
                                    readonly>
                            </td>

                            {{-- Total Custo (bruto) --}}
                            <td class="px-2 py-1 border text-right w-28">
                                <input type="text" name="itens[0][total_custo]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-total-custo"
                                    readonly>
                            </td>

                            {{-- Total Revenda --}}
                            <td class="px-2 py-1 border text-right w-28">
                                <input type="text" name="itens[0][total_revenda]"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right input-total-revenda"
                                    readonly>
                            </td>

                            {{-- Lucro --}}
                            <td class="px-2 py-1 border text-right w-24">
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

                            {{-- Total líquido (custo - desconto), só para cálculo geral --}}
                            <input type="hidden" name="itens[0][total_liquido]" class="input-total-liquido">
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Botões + Totais --}}
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <button type="button" id="btnAddItem"
                        class="px-3 py-1 bg-blue-500 text-white text-sm rounded shadow">
                        + Adicionar item
                    </button>

                    <button type="button" id="btnImportar"
                        class="px-3 py-1 bg-blue-500 text-white text-sm rounded shadow">
                        Importar Itens (CSV)
                    </button>

                    <input type="file" id="arquivoImportacao" accept=".csv,.txt" class="hidden">

                    <span class="text-xs text-gray-500">
                        Formato: CÓDIGO;QTD;PONTOS;PREÇOCOMPRA;PREÇOREVENDA
                    </span>
                </div>

                {{-- Direita: totais --}}
                <div class="flex items-center gap-4 text-sm">
                    <div>Custo Líquido: <span id="totalCustoSpan">0,00</span></div>
                    <div>Revenda: <span id="totalRevendaSpan">0,00</span></div>
                    <div class="font-semibold">
                        Lucro: <span id="totalLucroSpan">0,00</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('compras.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded shadow text-sm">
                    Cancelar
                </a>

                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded shadow text-sm">
                    Salvar Pedido
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
            let indice = 0;
            
            // === PREENCHE QTDE DE PARCELAS AO SELECIONAR O PLANO ===
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

                // Se já veio valor antigo (old) com plano selecionado, forçar disparar ao carregar
                if (planoSelect.value) {
                    const opt = planoSelect.options[planoSelect.selectedIndex];
                    const parcelas = opt ? opt.getAttribute('data-parcelas') : null;
                    if (parcelas && !parcelasInput.value) {
                        parcelasInput.value = parcelas;
                    }
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
                    ajax: {
                        url: '{{ route('produtos.lookup') }}',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                q: params.term
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
                    row.querySelector('.input-preco-revenda').value = (dados.preco_revenda ?? 0).toFixed(2);
                    row.querySelector('.input-pontos').value = dados.pontos ?? 0;

                    recalcularLinha(row);
                    recalcularTotalGeral();
                });

                // eventos de recálculo
                $(row).find('.input-quantidade, .input-desconto').on('input', function() {
                    recalcularLinha(row);
                    recalcularTotalGeral();
                });
            }

            function recalcularLinha(row) {
                const qty = toNumber(row.querySelector('.input-quantidade').value);
                const pcomp = toNumber(row.querySelector('.input-preco-compra').value);
                const prev = toNumber(row.querySelector('.input-preco-revenda').value);
                const descLinha = toNumber(row.querySelector('.input-desconto').value);

                const totalCustoBruto = qty * pcomp;
                const totalRevenda = qty * prev;
                const totalLiquido = Math.max(0, totalCustoBruto - descLinha);
                const lucro = totalRevenda - totalLiquido;

                row.querySelector('.input-total-custo').value = totalCustoBruto.toFixed(2);
                row.querySelector('.input-total-revenda').value = totalRevenda.toFixed(2);
                row.querySelector('.input-lucro-linha').value = lucro.toFixed(2);
                row.querySelector('.input-total-liquido').value = totalLiquido.toFixed(2);
            }

            function recalcularTotalGeral() {
                let totalLiquido = 0;
                let totalRevenda = 0;

                document.querySelectorAll('.input-total-liquido').forEach(function(inp) {
                    totalLiquido += toNumber(inp.value);
                });

                document.querySelectorAll('.input-total-revenda').forEach(function(inp) {
                    totalRevenda += toNumber(inp.value);
                });

                const totalLucro = totalRevenda - totalLiquido;

                document.getElementById('totalCustoSpan').innerText = totalLiquido.toFixed(2).replace('.', ',');
                document.getElementById('totalRevendaSpan').innerText = totalRevenda.toFixed(2).replace('.', ',');
                document.getElementById('totalLucroSpan').innerText = totalLucro.toFixed(2).replace('.', ',');
            }

            function adicionarLinha() {
                const tbody = document.getElementById('tbody-itens');
                const modelo = tbody.querySelector('.linha-item');

                indice++;

                const novaLinha = modelo.cloneNode(true);
                novaLinha.dataset.index = indice;

                // limpa inputs
                novaLinha.querySelectorAll('input').forEach(function(inp) {
                    inp.value = '';
                });
                novaLinha.querySelector('.input-quantidade').value = 1;
                novaLinha.querySelector('.input-desconto').value = 0;

                // ajusta names
                novaLinha.querySelector('.input-codfab')
                    .setAttribute('name', 'itens[' + indice + '][codfabnumero]');
                novaLinha.querySelector('.input-produto-id')
                    .setAttribute('name', 'itens[' + indice + '][produto_id]');
                novaLinha.querySelector('.input-quantidade')
                    .setAttribute('name', 'itens[' + indice + '][quantidade]');
                novaLinha.querySelector('.input-pontos')
                    .setAttribute('name', 'itens[' + indice + '][pontos]');
                novaLinha.querySelector('.input-preco-compra')
                    .setAttribute('name', 'itens[' + indice + '][preco_compra]');
                novaLinha.querySelector('.input-desconto')
                    .setAttribute('name', 'itens[' + indice + '][desconto]');
                novaLinha.querySelector('.input-preco-revenda')
                    .setAttribute('name', 'itens[' + indice + '][preco_revenda]');
                novaLinha.querySelector('.input-total-custo')
                    .setAttribute('name', 'itens[' + indice + '][total_custo]');
                novaLinha.querySelector('.input-total-revenda')
                    .setAttribute('name', 'itens[' + indice + '][total_revenda]');
                novaLinha.querySelector('.input-total-liquido')
                    .setAttribute('name', 'itens[' + indice + '][total_liquido]');

                // recria select
                let selectTd = novaLinha.querySelector('.produto-select').parentElement;
                selectTd.innerHTML =
                    '<select class="produto-select w-full" data-index="' + indice +
                    '" style="width:100%;"></select>' +
                    '<input type="hidden" name="itens[' + indice + '][codfabnumero]" class="input-codfab">' +
                    '<input type="hidden" name="itens[' + indice + '][produto_id]" class="input-produto-id">';

                // botão remover
                novaLinha.querySelector('.btn-remover-linha').addEventListener('click', function() {
                    novaLinha.remove();
                    recalcularTotalGeral();
                });

                tbody.appendChild(novaLinha);
                initSelect2(novaLinha);

                return novaLinha;
            }

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
                    recalcularTotalGeral();
                }
            });

            // Importação CSV
            const inputArquivo = document.getElementById('arquivoImportacao');
            document.getElementById('btnImportar').addEventListener('click', function() {
                inputArquivo.click();
            });

            inputArquivo.addEventListener('change', function(e) {
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
                        const pontos = partes[2] ? toNumber(partes[2]) : null;
                        const precoCompra = partes[3] ? toNumber(partes[3]) : null;
                        const precoRevenda = partes[4] ? toNumber(partes[4]) : null;

                        if (!codigo) return;

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
                                    console.warn(
                                        'Produto não encontrado na view para código',
                                        codigo);
                                    return;
                                }

                                let prod = data.find(p => p.codigo_fabrica ==
                                    codigo) || data[0];

                                const option = new Option(prod.text, prod.id, true,
                                    true);
                                $select.append(option).trigger('change');

                                $select.trigger({
                                    type: 'select2:select',
                                    params: {
                                        data: prod
                                    }
                                });

                                if (qtd > 0) {
                                    novaLinha.querySelector('.input-quantidade')
                                        .value = qtd;
                                }
                                if (pontos !== null && !isNaN(pontos)) {
                                    novaLinha.querySelector('.input-pontos').value =
                                        pontos;
                                }
                                if (precoCompra !== null && !isNaN(precoCompra)) {
                                    novaLinha.querySelector('.input-preco-compra')
                                        .value = precoCompra.toFixed(2);
                                }
                                if (precoRevenda !== null && !isNaN(precoRevenda)) {
                                    novaLinha.querySelector('.input-preco-revenda')
                                        .value = precoRevenda.toFixed(2);
                                }

                                recalcularLinha(novaLinha);
                                recalcularTotalGeral();
                            }
                        });
                    });
                };

                reader.readAsText(file, 'UTF-8');
            });
        });
    </script>
</x-app-layout>
