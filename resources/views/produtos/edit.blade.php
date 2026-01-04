<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Produto</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('produtos.update', $produto->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código Fabricante</label>
                    <input type="text" name="codfab" value="{{ $produto->codfab }}"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ $produto->nome }}"
                        class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ $produto->descricao }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                    <input type="file" name="imagem" accept="image/*"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*"
                        class="w-full border-gray-300 rounded-md shadow-sm" onchange="previewImagem(event)">

                    <div class="mt-3 flex gap-4 items-center">
                        {{-- Imagem atual --}}
                        @if ($produto->imagem)
                            <div class="border border-gray-300 rounded-md overflow-hidden shadow"
                                style="width: 100px; height: 100px;">
                                <img id="previewAtual" src="{{ asset($produto->imagem) }}" alt="Imagem Atual"
                                    class="object-cover w-full h-full rounded-md">
                            </div>
                        @endif

                        {{-- Nova imagem (preview) --}}
                        <div class="border border-gray-300 rounded-md flex items-center justify-center overflow-hidden shadow"
                            style="width: 100px; height: 100px;">
                            <img id="preview" src="#" alt="Nova Imagem"
                                class="hidden object-cover w-full h-full rounded-md">
                        </div>
                    </div>
                </div>

                <script>
                    function previewImagem(event) {
                        const [file] = event.target.files;
                        const preview = document.getElementById('preview');
                        const previewAtual = document.getElementById('previewAtual');

                        if (file) {
                            preview.src = URL.createObjectURL(file);
                            preview.classList.remove('hidden');
                            if (previewAtual) previewAtual.classList.add('hidden');
                        } else {
                            preview.classList.add('hidden');
                            if (previewAtual) previewAtual.classList.remove('hidden');
                        }
                    }
                </script>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}"
                                {{ $produto->categoria_id == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Subcategoria</label>
                    <select name="subcategoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach ($subcategorias as $sub)
                            <option value="{{ $sub->id }}"
                                {{ $produto->subcategoria_id == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="tipo" class="block text-sm font-medium text-gray-700">
                        Tipo de cadastro
                    </label>
                    <select name="tipo" id="tipo"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @php
                            $tipoSelecionado = old('tipo', $produto->tipo ?? 'P');
                        @endphp

                        <option value="P" {{ $tipoSelecionado === 'P' ? 'selected' : '' }}>
                            Produto unitário
                        </option>
                        <option value="K" {{ $tipoSelecionado === 'K' ? 'selected' : '' }}>
                            Kit (composto por outros produtos)
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Produto unitário = item normal (sabonete, perfume, etc).<br>
                        Kit = agrupamento desses produtos (ex.: Kit Presente TodoDia).
                    </p>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach ($fornecedores as $for)
                            <option value="{{ $for->id }}"
                                {{ $produto->fornecedor_id == $for->id ? 'selected' : '' }}>
                                {{ $for->nomefantasia }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SOMENTE PARA KITS --}}
                @if ($produto->tipo === 'K')
                    <div class="col-span-2 mt-4">
                        <hr class="my-6">

                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Composição do Kit
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Comece a digitar o nome ou código do produto, selecione na lista e informe a
                                    quantidade.
                                    Depois clique em ✅ para confirmar o item.
                                </p>
                            </div>

                            <button type="button" id="btn-add-kit-item"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-md
                       border border-indigo-500 text-indigo-600 bg-white hover:bg-indigo-50
                       disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Adicionar item" disabled>
                                ➕
                            </button>
                        </div>

                        <div class="relative">
                            <table class="w-full text-sm border border-gray-200 rounded-md" id="tabela-kit-itens">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left border-b text-xs font-medium text-gray-600 w-2/3">
                                            Produto
                                        </th>
                                        <th class="px-3 py-2 text-left border-b text-xs font-medium text-gray-600 w-24">
                                            Qtde
                                        </th>
                                        <th
                                            class="px-3 py-2 text-center border-b text-xs font-medium text-gray-600 w-24">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>

                                <tbody id="kit-itens-tbody">
                                    {{-- Linhas já cadastradas (iniciam CONFIRMADAS) --}}
                                    @foreach ($produto->itensDoKit as $itemKit)
                                        @php
                                            $produtoItem = $itemKit->produtoItem ?? null;
                                            $textoProduto = $produtoItem
                                                ? $produtoItem->codfabnumero . ' - ' . $produtoItem->nome
                                                : '';
                                        @endphp

                                        <tr class="border-b kit-item-row" data-confirmed="1">
                                            <td class="px-3 py-2">
                                                <div class="relative">
                                                    <input type="text"
                                                        class="kit-produto-busca w-full border-gray-300 rounded-md shadow-sm text-xs bg-gray-50"
                                                        placeholder="Digite para buscar..." autocomplete="off"
                                                        value="{{ $textoProduto }}" disabled>

                                                    <input type="hidden" name="kit_itens_produto_id[]"
                                                        class="kit-produto-id" value="{{ $produtoItem->id ?? '' }}">

                                                    <div
                                                        class="kit-suggestions absolute left-0 right-0 top-full mt-1
                                               bg-white border border-gray-200 rounded-md shadow-lg z-50
                                               text-sm max-h-80 overflow-y-auto py-1 hidden">
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-3 py-2">
                                                <input type="text" name="kit_itens_quantidade[]"
                                                    value="{{ number_format($itemKit->quantidade, 3, ',', '') }}"
                                                    class="w-24 border-gray-300 rounded-md shadow-sm text-xs text-right bg-gray-50"
                                                    disabled>
                                            </td>

                                            <td class="px-3 py-2">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button"
                                                        class="kit-btn-edit inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-gray-50"
                                                        title="Editar">
                                                        ✏️
                                                    </button>

                                                    <button type="button"
                                                        class="kit-btn-remove inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-red-50"
                                                        title="Remover">
                                                        ❌
                                                    </button>

                                                    <button type="button"
                                                        class="kit-btn-confirm hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-green-50"
                                                        title="Confirmar">
                                                        ✅
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Template oculto para novas linhas (iniciam PENDENTES) --}}
                        <template id="kit-item-row-template">
                            <tr class="border-b kit-item-row" data-confirmed="0">
                                <td class="px-3 py-2">
                                    <div class="relative">
                                        <input type="text"
                                            class="kit-produto-busca w-full border-gray-300 rounded-md shadow-sm text-xs"
                                            placeholder="Digite para buscar..." autocomplete="off">

                                        <input type="hidden" name="kit_itens_produto_id[]" class="kit-produto-id"
                                            value="">

                                        <div
                                            class="kit-suggestions absolute left-0 right-0 top-full mt-1
                                   bg-white border border-gray-200 rounded-md shadow-lg z-50
                                   text-sm max-h-80 overflow-y-auto py-1 hidden">
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    <input type="text" name="kit_itens_quantidade[]" value="1,000"
                                        class="w-24 border-gray-300 rounded-md shadow-sm text-xs text-right"
                                        placeholder="1,000">
                                </td>

                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button"
                                            class="kit-btn-edit hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-gray-50"
                                            title="Editar">
                                            ✏️
                                        </button>

                                        <button type="button"
                                            class="kit-btn-remove inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-red-50"
                                            title="Remover">
                                            ❌
                                        </button>

                                        <button type="button"
                                            class="kit-btn-confirm inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 hover:bg-green-50"
                                            title="Confirmar">
                                            ✅
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const btnAdd = document.getElementById('btn-add-kit-item');
                                const tbody = document.getElementById('kit-itens-tbody');
                                const tpl = document.getElementById('kit-item-row-template');
                                const lookupUrl = '{{ route('produtos.lookup') }}';
                                const form = tbody ? tbody.closest('form') : null;

                                function parseQuantidadeBr(v) {
                                    // "1,000" -> 1.000
                                    if (v === null || v === undefined) return 0;
                                    v = String(v).trim();
                                    if (!v) return 0;

                                    // remove espaços
                                    v = v.replace(/\s+/g, '');

                                    // se vier "1.234,567" vira "1234.567"
                                    // se vier "1,000" vira "1.000"
                                    // se vier "1.000" (usuário digitou ponto) mantém
                                    const hasComma = v.includes(',');
                                    const hasDot = v.includes('.');

                                    if (hasComma && hasDot) {
                                        // padrão BR completo
                                        v = v.replace(/\./g, '').replace(',', '.');
                                    } else if (hasComma && !hasDot) {
                                        v = v.replace(',', '.');
                                    }

                                    const n = parseFloat(v);
                                    return isNaN(n) ? 0 : n;
                                }

                                function formatQuantidadeBr(n) {
                                    // mantém 3 casas
                                    const x = (Math.round(n * 1000) / 1000).toFixed(3);
                                    return x.replace('.', ',');
                                }

                                function rowSetConfirmed(row, confirmed) {
                                    row.dataset.confirmed = confirmed ? '1' : '0';

                                    const inputBusca = row.querySelector('.kit-produto-busca');
                                    const inputQtd = row.querySelector('input[name="kit_itens_quantidade[]"]');

                                    const btnConfirm = row.querySelector('.kit-btn-confirm');
                                    const btnEdit = row.querySelector('.kit-btn-edit');

                                    if (confirmed) {
                                        if (inputBusca) {
                                            inputBusca.disabled = true;
                                            inputBusca.classList.add('bg-gray-50');
                                        }
                                        if (inputQtd) {
                                            inputQtd.disabled = true;
                                            inputQtd.classList.add('bg-gray-50');
                                            // normaliza visual para 3 casas
                                            const q = parseQuantidadeBr(inputQtd.value);
                                            if (q > 0) inputQtd.value = formatQuantidadeBr(q);
                                        }

                                        if (btnConfirm) btnConfirm.classList.add('hidden');
                                        if (btnEdit) btnEdit.classList.remove('hidden');
                                    } else {
                                        if (inputBusca) {
                                            inputBusca.disabled = false;
                                            inputBusca.classList.remove('bg-gray-50');
                                        }
                                        if (inputQtd) {
                                            inputQtd.disabled = false;
                                            inputQtd.classList.remove('bg-gray-50');
                                        }

                                        if (btnConfirm) btnConfirm.classList.remove('hidden');
                                        if (btnEdit) btnEdit.classList.add('hidden');
                                    }

                                    refreshAddButtonState();
                                }

                                function allRowsConfirmed() {
                                    const rows = tbody.querySelectorAll('.kit-item-row');
                                    if (!rows.length) return false;
                                    for (const r of rows) {
                                        if (r.dataset.confirmed !== '1') return false;
                                    }
                                    return true;
                                }

                                function refreshAddButtonState() {
                                    if (!btnAdd) return;

                                    const rows = tbody.querySelectorAll('.kit-item-row');
                                    if (!rows.length) {
                                        btnAdd.disabled = false;
                                        return;
                                    }

                                    btnAdd.disabled = !allRowsConfirmed();
                                }

                                function validateRow(row) {
                                    const inputId = row.querySelector('.kit-produto-id');
                                    const inputBusca = row.querySelector('.kit-produto-busca');
                                    const inputQtd = row.querySelector('input[name="kit_itens_quantidade[]"]');

                                    const produtoId = (inputId?.value || '').trim();
                                    const qtd = parseQuantidadeBr(inputQtd?.value || '');

                                    if (!produtoId) {
                                        alert('Selecione um produto na lista antes de confirmar.');
                                        inputBusca?.focus();
                                        return false;
                                    }

                                    if (!(qtd > 0)) {
                                        alert('Informe uma quantidade válida (> 0) antes de confirmar.');
                                        inputQtd?.focus();
                                        return false;
                                    }

                                    return true;
                                }

                                function initKitRow(row) {
                                    const inputBusca = row.querySelector('.kit-produto-busca');
                                    const inputId = row.querySelector('.kit-produto-id');
                                    const box = row.querySelector('.kit-suggestions');

                                    const btnConfirm = row.querySelector('.kit-btn-confirm');
                                    const btnEdit = row.querySelector('.kit-btn-edit');
                                    const btnRemove = row.querySelector('.kit-btn-remove');

                                    let timer = null;

                                    if (!inputBusca || !inputId || !box) return;

                                    // lookup
                                    inputBusca.addEventListener('input', function() {
                                        if (row.dataset.confirmed === '1') return;

                                        const termo = inputBusca.value.trim();
                                        inputId.value = '';

                                        if (timer) clearTimeout(timer);

                                        if (termo.length < 2) {
                                            box.innerHTML = '';
                                            box.classList.add('hidden');
                                            return;
                                        }

                                        timer = setTimeout(function() {
                                            fetch(lookupUrl + '?q=' + encodeURIComponent(termo) + '&limit=15')
                                                .then(resp => resp.json())
                                                .then(data => {
                                                    box.innerHTML = '';
                                                    const resultados = Array.isArray(data) ? data : (data.results ||
                                                        []);

                                                    resultados.forEach(item => {
                                                        const produtoId = item.produto_id || item.id;

                                                        const div = document.createElement('div');
                                                        div.className =
                                                            'px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm';
                                                        div.textContent = item.text;
                                                        div.dataset.id = produtoId;

                                                        div.addEventListener('click', function() {
                                                            inputBusca.value = item.text;
                                                            inputId.value = produtoId;
                                                            box.innerHTML = '';
                                                            box.classList.add('hidden');
                                                        });

                                                        box.appendChild(div);
                                                    });

                                                    if (resultados.length > 0) box.classList.remove('hidden');
                                                    else box.classList.add('hidden');
                                                })
                                                .catch((e) => {
                                                    console.error('Erro no lookup de produto do kit:', e);
                                                    box.innerHTML = '';
                                                    box.classList.add('hidden');
                                                });
                                        }, 300);
                                    });

                                    inputBusca.addEventListener('blur', function() {
                                        setTimeout(function() {
                                            box.classList.add('hidden');
                                        }, 200);
                                    });

                                    // confirmar
                                    if (btnConfirm) {
                                        btnConfirm.addEventListener('click', function() {
                                            if (!validateRow(row)) return;
                                            rowSetConfirmed(row, true);
                                        });
                                    }

                                    // editar
                                    if (btnEdit) {
                                        btnEdit.addEventListener('click', function() {
                                            rowSetConfirmed(row, false);
                                            // quando entra em edição, trava o botão adicionar
                                            refreshAddButtonState();
                                            inputBusca.focus();
                                        });
                                    }

                                    // remover
                                    if (btnRemove) {
                                        btnRemove.addEventListener('click', function() {
                                            row.remove();
                                            refreshAddButtonState();

                                            // se ficou sem linhas, cria 1 linha pendente
                                            if (!tbody.querySelector('.kit-item-row')) {
                                                const clone = tpl.content.cloneNode(true);
                                                tbody.appendChild(clone);
                                                const primeira = tbody.querySelector('.kit-item-row');
                                                initKitRow(primeira);
                                                rowSetConfirmed(primeira, false);
                                            }
                                        });
                                    }
                                }

                                // inicializa linhas existentes
                                tbody.querySelectorAll('.kit-item-row').forEach(row => initKitRow(row));

                                // se não houver nenhum item, cria uma linha pendente
                                if (!tbody.querySelector('.kit-item-row')) {
                                    const clone = tpl.content.cloneNode(true);
                                    tbody.appendChild(clone);
                                    const primeira = tbody.querySelector('.kit-item-row');
                                    initKitRow(primeira);
                                    rowSetConfirmed(primeira, false);
                                } else {
                                    refreshAddButtonState();
                                }

                                // adicionar linha (só quando tudo confirmado)
                                if (btnAdd && tbody && tpl) {
                                    btnAdd.addEventListener('click', function() {
                                        if (btnAdd.disabled) return;

                                        const clone = tpl.content.cloneNode(true);
                                        tbody.appendChild(clone);

                                        const rows = tbody.querySelectorAll('.kit-item-row');
                                        const nova = rows[rows.length - 1];

                                        initKitRow(nova);
                                        rowSetConfirmed(nova, false);

                                        // ao adicionar, desabilita até confirmar
                                        refreshAddButtonState();

                                        const inputBusca = nova.querySelector('.kit-produto-busca');
                                        inputBusca?.focus();
                                    });
                                }

                                // bloqueia submit se houver linha pendente
                                if (form) {
                                    form.addEventListener('submit', function(e) {
                                        const rows = tbody.querySelectorAll('.kit-item-row');
                                        for (const row of rows) {
                                            if (row.dataset.confirmed !== '1') {
                                                e.preventDefault();
                                                alert(
                                                    'Existe item do KIT não confirmado. Clique em ✅ para confirmar antes de salvar.');
                                                const b = row.querySelector('.kit-produto-busca');
                                                b?.focus();
                                                return;
                                            }
                                            // revalida por segurança
                                            if (!validateRow(row)) {
                                                e.preventDefault();
                                                return;
                                            }
                                        }
                                    });
                                }
                            });
                        </script>
                    </div>
                @endif


                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $produto->status ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ !$produto->status ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('produtos.index') }}"
                    class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded mr-2">Voltar</a>
                <button type="submit"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
