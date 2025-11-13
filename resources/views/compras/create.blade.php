<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Pedido de Compra</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <form action="{{ route('compras.store') }}" method="POST" id="formCompra">
            @csrf

            {{-- Cabeçalho --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}">{{ $fornecedor->nomefantasia }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Número Pedido</label>
                    <input type="text" name="numpedcompra" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            {{-- Cabeçalho da Tabela --}}
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-700">Itens da Compra</h3>
                <button type="button" id="abrirImportacao"
                    style="background-color: #4f46e5; color: white; padding: 6px 16px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">
                📥 Importar Itens por Código
                </button>
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
                    <tr>
                        <td class="border p-2">
                            <select name="itens[0][produto_id]" class="w-full border-gray-300 rounded-md shadow-sm produtoSelect" required>
                                <option value="">Selecione...</option>
                                @foreach ($produtos as $produto)
                                    <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="border p-2">
                            <input type="number" step="0.01" min="0" name="itens[0][quantidade]" 
                                class="w-full text-center border-gray-300 rounded-md shadow-sm quantidade" required>
                        </td>
                        <td class="border p-2">
                            <input type="number" step="0.01" min="0" name="itens[0][preco_unitario]" 
                                class="w-full text-right border-gray-300 rounded-md shadow-sm preco-compra">
                        </td>
                        <td class="border p-2">
                            <input type="number" step="0.01" min="0" name="itens[0][preco_venda_unitario]" 
                                class="w-full text-right border-gray-300 rounded-md shadow-sm preco-venda">
                        </td>
                        <td class="border p-2 text-center">
                            <input type="number" step="1" min="0" name="itens[0][pontos]" 
                                class="w-full text-center border-gray-300 rounded-md shadow-sm pontos" value="0">
                        </td>
                        <td class="border p-2 text-right totalCompra">0.00</td>
                        <td class="border p-2 text-right totalVenda">0.00</td>
                        <td class="border p-2 text-center">
                            <button type="button" class="text-red-600 removerItem">✖</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-3">
                <button type="button" id="addItem" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                    + Adicionar Produto
                </button>
            </div>

            {{-- Totais --}}
            <div class="flex justify-end items-center mt-6 space-x-4">
                <div>
                    <label class="font-semibold text-gray-700">Total Compra:</label>
                    <input type="text" id="valor_total_display" class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="valor_total" name="valor_total">
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Total Venda:</label>
                    <input type="text" id="preco_venda_total_display" class="w-32 text-right border-gray-300 rounded-md shadow-sm" readonly>
                    <input type="hidden" id="preco_venda_total" name="preco_venda_total">
                </div>
            </div>

            {{-- Botões --}}
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <a href="{{ route('compras.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Salvar Pedido
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de Importação -->
    <div id="modalImportacao" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-[420px]" style="font-family: Arial, sans-serif;">

            <!-- Cabeçalho -->
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Importar Itens por Código</h3>
            <p class="text-sm text-gray-600 mb-3" style="line-height: 1.5;">
                Digite o <strong>código</strong>, <strong>quantidade</strong>, <strong>pontos</strong>,
                <strong>preço compra</strong> e <strong>preço venda</strong>, separados por ponto e vírgula, um por linha.<br>
                <span style="font-size: 12px; color: #888;">Exemplo: <code>12345;2;10;19.90;29.90</code></span>
            </p>

            <!-- Contador e limpar -->
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-bottom: 6px;">
                <span style="color: #444;">Linhas: <span id="contadorLinhas" style="font-weight: bold; color: #4f46e5;">0</span></span>
                <button type="button" id="limparImport"
                    style="color: #dc2626; font-size: 12px; cursor: pointer; text-decoration: underline;">Limpar</button>
            </div>

            <!-- Textarea -->
            <textarea id="importText" rows="8"
                class="w-full border border-gray-300 rounded-md shadow-sm text-sm p-2 mb-4 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 resize-none"
                style="width: 100%; border: 1px solid #ccc; border-radius: 6px; padding: 8px; font-size: 13px; margin-bottom: 14px; resize: none;"
                placeholder="237283;2;10;19.90;29.90&#10;126265;1;5;15.50;25.00"></textarea>

            <!-- Indicador de progresso -->
            <div id="importProgress" class="hidden" style="margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; color: #555; font-size: 13px; margin-bottom: 5px;">
                    <svg class="animate-spin" style="height: 18px; width: 18px; color: #4f46e5;" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="progressText">Importando itens...</span>
                </div>
                <div style="width: 100%; height: 8px; background-color: #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div id="progressBar" style="background-color: #4f46e5; height: 8px; width: 0%; border-radius: 4px; transition: width 0.3s;"></div>
                </div>
            </div>

            <!-- Botões -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <button type="button" id="cancelarImportacao"
                    style="background-color: #6b7280; color: white; padding: 6px 14px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" id="btnImportar"
                    style="background-color: #4f46e5; color: white; padding: 6px 16px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">
                    Importar
                </button>
            </div>

        </div>
    </div>

    {{-- 🧩 DEPENDÊNCIAS DO SELECT2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- SCRIPT PRINCIPAL --}}
    <script>
        const baseUrl = "{{ url('') }}";
        let itemIndex = 1;

        // ➕ Adicionar nova linha
        document.getElementById('addItem').addEventListener('click', () => {
            const tbody = document.getElementById('tbody-itens');
            const newRow = tbody.rows[0].cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(el => {
                el.value = '';
                const name = el.getAttribute('name');
                el.setAttribute('name', name.replace(/\[\d+\]/, `[${itemIndex}]`));
            });
            newRow.querySelector('.totalCompra').textContent = '0.00';
            newRow.querySelector('.totalVenda').textContent = '0.00';
            tbody.appendChild(newRow);
            itemIndex++;
            setTimeout(() => aplicarSelect2(), 100); // reaplica select2
        });

        // ❌ Remover linha
        document.addEventListener('click', e => {
            if (e.target.classList.contains('removerItem')) {
                const rows = document.querySelectorAll('#tbody-itens tr');
                if (rows.length > 1) e.target.closest('tr').remove();
                calcularTotais();
            }
        });

        // 🔁 Buscar preços automaticamente ao selecionar manualmente um produto
        document.addEventListener('change', async e => {
            if (e.target.classList.contains('produtoSelect')) {
                const produtoId = e.target.value;
                const linha = e.target.closest('tr');
                if (produtoId) {
                    const resp = await fetch(`${baseUrl}/produto/preco/${produtoId}`);
                    const data = await resp.json();
                    linha.querySelector('.preco-compra').value = parseFloat(data.preco_compra || 0).toFixed(2);
                    linha.querySelector('.preco-venda').value = parseFloat(data.preco_venda || 0).toFixed(2);
                    calcularTotais();
                }
            }
        });

        // 🧮 Calcular totais
        function calcularTotais() {
            let totalCompra = 0, totalVenda = 0;
            document.querySelectorAll('#tbody-itens tr').forEach(linha => {
                const qtd = parseFloat(linha.querySelector('.quantidade').value) || 0;
                const precoCompra = parseFloat(linha.querySelector('.preco-compra').value) || 0;
                const precoVenda = parseFloat(linha.querySelector('.preco-venda').value) || 0;
                const totalC = qtd * precoCompra;
                const totalV = qtd * precoVenda;
                linha.querySelector('.totalCompra').textContent = totalC.toFixed(2);
                linha.querySelector('.totalVenda').textContent = totalV.toFixed(2);
                totalCompra += totalC;
                totalVenda += totalV;
            });
            document.getElementById('valor_total_display').value = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total_display').value = totalVenda.toFixed(2);
            document.getElementById('valor_total').value = totalCompra.toFixed(2);
            document.getElementById('preco_venda_total').value = totalVenda.toFixed(2);
        }

        // 🟢 Abrir/fechar modal
        const modal = document.getElementById('modalImportacao');
        document.getElementById('abrirImportacao').addEventListener('click', () => modal.classList.remove('hidden'));
        document.getElementById('cancelarImportacao').addEventListener('click', () => modal.classList.add('hidden'));

        // 🧮 Contador de linhas no modal de importação
        const importText = document.getElementById('importText');
        const contadorLinhas = document.getElementById('contadorLinhas');
        const limparImport = document.getElementById('limparImport');

        if (importText) {
            importText.addEventListener('input', () => {
                const linhas = importText.value.trim().split('\n').filter(l => l.trim() !== '').length;
                contadorLinhas.textContent = linhas;
            });
        }

        if (limparImport) {
            limparImport.addEventListener('click', () => {
                importText.value = '';
                contadorLinhas.textContent = '0';
                importText.focus();
            });
        }

        // 🟢 Importar produtos via código e quantidade (SEM timer extra, só percentual)
        document.getElementById('btnImportar').addEventListener('click', async () => {
            const texto = importText.value.trim();
            const progressContainer = document.getElementById('importProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            if (!texto) {
                alert('Informe pelo menos um produto.');
                return;
            }

            const linhas = texto.split('\n').filter(l => l.trim() !== '');
            const totalLinhas = linhas.length;
            const tbody = document.getElementById('tbody-itens');
            let idx = tbody.querySelectorAll('tr').length;
            const produtosNaoEncontrados = [];

            // mostra barra de progresso
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = `Importando (0/${totalLinhas})...`;

            for (let i = 0; i < totalLinhas; i++) {
                const linha = linhas[i];
                const [codfabnumero, qtdStr, pontosStr, precoCompraStr, precoVendaStr] =
                    linha.split(';').map(s => s.trim());

                const qtd = parseFloat(qtdStr || 0);
                const precoCompraArquivo = parseFloat(precoCompraStr || 0);
                const precoVendaArquivo = parseFloat(precoVendaStr || 0);
                const pontos = parseFloat(pontosStr || 0);

                if (!codfabnumero || qtd <= 0) continue;

                try {
                    const resp = await fetch(`${baseUrl}/produto/bycod/${codfabnumero}`);
                    const data = await resp.json();

                    if (data && data.id) {
                        const newRow = tbody.rows[0].cloneNode(true);

                        // renomeia os campos com novo índice
                        newRow.querySelectorAll('input, select').forEach(el => {
                            const name = el.getAttribute('name');
                            if (name) el.setAttribute('name', name.replace(/\[\d+\]/, `[${idx}]`));
                        });

                        // produto
                        newRow.querySelector('.produtoSelect').value = data.id;
                        // quantidade
                        newRow.querySelector('.quantidade').value = qtd;

                        // pontos
                        const inputPontos = newRow.querySelector('.pontos');
                        if (inputPontos) inputPontos.value = isNaN(pontos) ? 0 : pontos;

                        // preços: se vier no arquivo usa, senão cai pro da tabela
                        const precoCompra = precoCompraArquivo > 0
                            ? precoCompraArquivo
                            : parseFloat(data.preco_compra || 0);

                        const precoVenda = precoVendaArquivo > 0
                            ? precoVendaArquivo
                            : parseFloat(data.preco_venda || 0);

                        newRow.querySelector('.preco-compra').value = precoCompra.toFixed(2);
                        newRow.querySelector('.preco-venda').value = precoVenda.toFixed(2);

                        const totalCompra = qtd * precoCompra;
                        const totalVenda = qtd * precoVenda;
                        newRow.querySelector('.totalCompra').textContent = totalCompra.toFixed(2);
                        newRow.querySelector('.totalVenda').textContent = totalVenda.toFixed(2);

                        tbody.appendChild(newRow);
                        idx++;
                    } else {
                        produtosNaoEncontrados.push(codfabnumero);
                    }
                } catch (error) {
                    produtosNaoEncontrados.push(codfabnumero);
                }

                const progresso = Math.round(((i + 1) / totalLinhas) * 100);
                progressBar.style.width = progresso + '%';
                progressText.textContent = `Importando (${i + 1}/${totalLinhas})...`;

                // pequena pausa só pra animação ficar visível
                await new Promise(r => setTimeout(r, 30));
            }

            calcularTotais();

            // hide barra e fechar modal
            progressBar.style.width = '100%';
            progressText.textContent = 'Importação concluída!';

            // TXT de não encontrados
            if (produtosNaoEncontrados.length > 0) {
                const blob = new Blob([produtosNaoEncontrados.join('\n')], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'produtos_nao_encontrados.txt';
                link.click();
                URL.revokeObjectURL(url);
                alert(`Importação concluída com ${produtosNaoEncontrados.length} código(s) não encontrado(s).`);
            } else {
                alert('Importação concluída com sucesso!');
            }

            progressContainer.classList.add('hidden');
            document.getElementById('modalImportacao').classList.add('hidden');
        });

        // 🧩 Ativar Select2
        function aplicarSelect2() {
            $('.produtoSelect').select2({
                width: '100%',
                placeholder: 'Selecione um produto...',
                allowClear: true,
                language: {
                    noResults: () => "Nenhum produto encontrado",
                    searching: () => "Buscando..."
                }
            });
        }
        document.addEventListener("DOMContentLoaded", aplicarSelect2);
    </script>

</x-app-layout>
