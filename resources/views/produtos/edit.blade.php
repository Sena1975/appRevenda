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
                    <hr class="my-6">

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        Composição do Kit
                    </h3>
                    <p class="text-xs text-gray-500 mb-3">
                        Comece a digitar o nome ou código do produto, selecione na lista e informe a quantidade.
                    </p>

                    <div class="relative">
                        <table class="min-w-full text-sm border border-gray-200 rounded-md" id="tabela-kit-itens">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left border-b text-xs font-medium text-gray-600 w-2/3">
                                        Produto
                                    </th>

                                    <th class="px-3 py-2 text-left border-b text-xs font-medium text-gray-600 w-24">
                                        Qtde
                                    </th>
                                    <th class="px-3 py-2 text-left border-b text-xs font-medium text-gray-600 w-16">
                                        Ação
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="kit-itens-tbody">
                                {{-- Linhas já cadastradas --}}
                                @foreach ($produto->itensDoKit as $itemKit)
                                    @php
                                        $produtoItem = $itemKit->produtoItem ?? null;
                                        $textoProduto = $produtoItem
                                            ? $produtoItem->codfabnumero . ' - ' . $produtoItem->nome
                                            : '';
                                    @endphp
                                    <tr class="border-b kit-item-row">
                                        <td class="px-3 py-2">
                                            <div class="relative">
                                                <input type="text"
                                                    class="kit-produto-busca w-full border-gray-300 rounded-md shadow-sm text-xs"
                                                    placeholder="Digite para buscar..." autocomplete="off"
                                                    value="{{ $textoProduto }}">
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
                                                class="w-24 border-gray-300 rounded-md shadow-sm text-xs text-right">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" class="text-xs text-red-600 hover:text-red-800"
                                                onclick="removerLinhaKit(this)">
                                                Remover
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="btn-add-kit-item"
                            class="inline-flex items-center px-3 py-1.5 border border-indigo-500 text-xs font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                            + Adicionar item
                        </button>
                    </div>

                    {{-- Template oculto para novas linhas --}}
                    <template id="kit-item-row-template">
                        <tr class="border-b kit-item-row">
                            <td class="px-3 py-2">
                                <div class="relative">
                                    <input type="text"
                                        class="kit-produto-busca w-full border-gray-300 rounded-md shadow-sm text-xs"
                                        placeholder="Digite para buscar..." autocomplete="off">
                                    <input type="hidden" name="kit_itens_produto_id[]" class="kit-produto-id"
                                        value="">
                                    <div
                                        class="kit-suggestions absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow z-20 text-xs hidden">
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="kit_itens_quantidade[]" value=""
                                    class="w-24 border-gray-300 rounded-md shadow-sm text-xs text-right"
                                    placeholder="1,000">
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" class="text-xs text-red-600 hover:text-red-800"
                                    onclick="removerLinhaKit(this)">
                                    Remover
                                </button>
                            </td>
                        </tr>
                    </template>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const btnAdd = document.getElementById('btn-add-kit-item');
                            const tbody = document.getElementById('kit-itens-tbody');
                            const tpl = document.getElementById('kit-item-row-template');
                            const lookupUrl = '{{ route('produtos.lookup') }}';

                            function initKitRow(row) {
                                const inputBusca = row.querySelector('.kit-produto-busca');
                                const inputId = row.querySelector('.kit-produto-id');
                                const box = row.querySelector('.kit-suggestions');
                                let timer = null;

                                if (!inputBusca || !inputId || !box) {
                                    return;
                                }

                                inputBusca.addEventListener('input', function() {
                                    const termo = inputBusca.value.trim();
                                    // sempre que digitar, limpa o id até escolher de novo
                                    inputId.value = '';

                                    if (timer) {
                                        clearTimeout(timer);
                                    }

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

                                                // SEU CONTROLLER DEVOLVE UM ARRAY DIRETO
                                                const resultados = Array.isArray(data) ?
                                                    data :
                                                    (data.results || []);

                                                resultados.forEach(item => {
                                                    // usamos produto_id se existir, senão cai no id
                                                    const produtoId = item.produto_id || item.id;

                                                    const div = document.createElement('div');
                                                    div.className =
                                                        'px-2 py-1 hover:bg-indigo-50 cursor-pointer';
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

                                                if (resultados.length > 0) {
                                                    box.classList.remove('hidden');
                                                } else {
                                                    box.classList.add('hidden');
                                                }
                                            })
                                            .catch((e) => {
                                                console.error('Erro no lookup de produto do kit:', e);
                                                box.innerHTML = '';
                                                box.classList.add('hidden');
                                            });
                                    }, 300);
                                });

                                // Esconde a lista um pouquinho depois de perder o foco
                                inputBusca.addEventListener('blur', function() {
                                    setTimeout(function() {
                                        box.classList.add('hidden');
                                    }, 200);
                                });
                            }

                            if (btnAdd && tbody && tpl) {
                                // Inicializa linhas já existentes (itensDoKit)
                                tbody.querySelectorAll('.kit-item-row').forEach(row => {
                                    initKitRow(row);
                                });

                                // Se não houver nenhum item cadastrado, cria uma linha vazia
                                if (!tbody.querySelector('.kit-item-row')) {
                                    const clone = tpl.content.cloneNode(true);
                                    tbody.appendChild(clone);
                                    const primeiraLinha = tbody.querySelector('.kit-item-row');
                                    initKitRow(primeiraLinha);
                                }

                                // Botão para adicionar novas linhas
                                btnAdd.addEventListener('click', function() {
                                    const clone = tpl.content.cloneNode(true);
                                    tbody.appendChild(clone);
                                    const linhas = tbody.querySelectorAll('.kit-item-row');
                                    const novaLinha = linhas[linhas.length - 1];
                                    initKitRow(novaLinha);
                                });
                            }
                        });

                        function removerLinhaKit(botao) {
                            const tr = botao.closest('tr');
                            const tbody = document.getElementById('kit-itens-tbody');
                            if (tr && tbody) {
                                tbody.removeChild(tr);
                            }
                        }
                    </script>

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
