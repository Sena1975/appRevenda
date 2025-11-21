<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Importar produtos de TXT (itens não importados)
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">

        {{-- Mensagens de sucesso --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Erros --}}
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

        @if (empty($itens))
            {{-- PASSO 1: upload do TXT --}}
            <form action="{{ route('produtos.importar_missing.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Arquivo TXT gerado na importação do pedido
                    </label>
                    <input type="file" name="arquivo_txt" class="border-gray-300 rounded-md shadow-sm text-sm w-full"
                        accept=".txt,text/plain" required>
                    <p class="mt-1 text-xs text-gray-500">
                        Use o arquivo <strong>itens_nao_importados_*.txt</strong> gerado na tela de venda.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded shadow text-sm">
                        Ler arquivo e montar produtos
                    </button>
                </div>
            </form>
        @else
            {{-- PASSO 2: conferência e gravação dos produtos --}}
            <form action="{{ route('produtos.importar_missing.store') }}" method="POST">
                @csrf
                <input type="hidden" name="salvar" value="1">

                <p class="mb-4 text-sm text-gray-600">
                    Confira os dados abaixo, ajuste descrição/preço se necessário e deixe marcado apenas
                    o que deseja cadastrar. O sistema também sugere um fornecedor e categoria/subcategoria
                    padrão, que você pode aplicar em todas as linhas ou ajustar por linha.
                </p>

                {{-- Seleções padrão --}}
                <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Fornecedor padrão
                        </label>
                        <select id="fornecedor_padrao" class="w-full border-gray-300 rounded-md shadow-sm text-xs">
                            <option value="">-- nenhum --</option>
                            @foreach ($fornecedores as $for)
                                <option value="{{ $for->id }}" @selected($fornecedorPadraoId == $for->id)>
                                    {{ $for->nomefantasia ?? ($for->razaosocial ?? ($for->nome ?? 'ID ' . $for->id)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Categoria padrão
                        </label>
                        <select id="categoria_padrao" class="w-full border-gray-300 rounded-md shadow-sm text-xs">
                            <option value="">-- nenhuma --</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}" @selected($categoriaPadraoId == $cat->id)>
                                    {{ $cat->nome ?? 'ID ' . $cat->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Subcategoria padrão
                        </label>
                        <div class="flex gap-2 items-center">
                            <select id="subcategoria_padrao"
                                class="flex-1 border-gray-300 rounded-md shadow-sm text-xs">
                                <option value="">-- nenhuma --</option>
                                @foreach ($subcategorias as $sub)
                                    <option value="{{ $sub->id }}" @selected($subcategoriaPadraoId == $sub->id)>
                                        {{ $sub->nome ?? 'ID ' . $sub->id }}
                                    </option>
                                @endforeach
                            </select>

                            <button id="btnAplicarPadrao" type="button"
                                class="px-3 py-1 bg-slate-600 text-white text-[11px] rounded shadow">
                                Aplicar padrões em todas as linhas
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full text-xs border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border text-center w-10">OK</th>
                                <th class="p-2 border w-20">Código</th>
                                <th class="p-2 border">Descrição</th>
                                <th class="p-2 border w-20">Fornecedor</th>
                                <th class="p-2 border w-20">Categoria</th>
                                <th class="p-2 border w-20">Subcategoria</th>
                                <th class="p-2 border w-16">Preco Compra</th>
                                <th class="p-2 border w-16">Pontuação</th>
                                <th class="p-2 border w-16 text-right">Preço Revenda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($itens as $idx => $item)
                                @php
                                    $precoRevenda = $item['preco_revenda'] ?? 0;
                                    $precoCompraSugerido = $precoRevenda > 0 ? $precoRevenda * 0.7 : 0;
                                @endphp
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                    <td class="p-2 border text-center">
                                        <input type="checkbox" name="itens[{{ $idx }}][importar]"
                                            value="1" checked>
                                    </td>

                                    {{-- Código --}}
                                    <td class="p-2 border">
                                        <input type="text" name="itens[{{ $idx }}][codfabnumero]"
                                            value="{{ $item['codfabnumero'] }}"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-xs">
                                    </td>

                                    <td class="p-2 border">
                                        <input type="text" name="itens[{{ $idx }}][nome]"
                                            value="{{ $item['nome'] }}"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-xs">
                                    </td>

                                    {{-- Fornecedor --}}
                                    <td class="p-2 border">
                                        <select name="itens[{{ $idx }}][fornecedor_id]"
                                            class="w-20 border-gray-300 rounded-md shadow-sm text-xs select-fornecedor-linha">
                                            <option value="">-- selecione --</option>
                                            @foreach ($fornecedores as $for)
                                                <option value="{{ $for->id }}" @selected($fornecedorPadraoId == $for->id)>
                                                    {{ $for->nomefantasia ?? ($for->razaosocial ?? ($for->nome ?? 'ID ' . $for->id)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Categoria --}}
                                    <td class="p-2 border">
                                        <select name="itens[{{ $idx }}][categoria_id]"
                                            class="w-20 border-gray-300 rounded-md shadow-sm text-xs select-categoria-linha">
                                            <option value="">-- selecione --</option>
                                            @foreach ($categorias as $cat)
                                                <option value="{{ $cat->id }}" @selected($categoriaPadraoId == $cat->id)>
                                                    {{ $cat->nome ?? 'ID ' . $cat->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Subcategoria --}}
                                    <td class="p-2 border">
                                        <select name="itens[{{ $idx }}][subcategoria_id]"
                                            class="w-20 border-gray-300 rounded-md shadow-sm text-xs select-subcategoria-linha">
                                            <option value="">-- selecione --</option>
                                            @foreach ($subcategorias as $sub)
                                                <option value="{{ $sub->id }}" @selected($subcategoriaPadraoId == $sub->id)>
                                                    {{ $sub->nome ?? 'ID ' . $sub->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Preço COMPRA -> sugerido 70% do revenda --}}
                                    <td>
                                        <input type="text" name="itens[{{ $idx }}][preco_compra]"
                                            class="w-20 border-gray-300 rounded text-right text-xs"
                                            value="{{ old('itens.' . $idx . '.preco_compra', number_format($precoCompraSugerido, 2, ',', '.')) }}">
                                    </td>
                                    {{-- Pontuação -> começa 0,00 --}}
                                    <td>
                                        <input type="text" name="itens[{{ $idx }}][pontuacao]"
                                            class="w-16 border-gray-300 rounded text-right text-xs"
                                            value="{{ old('itens.' . $idx . '.pontuacao', '0,00') }}">
                                    </td>

                                    {{-- Preço REVENDA -> sugerido a partir do TXT --}}
                                    <td>
                                        <input type="text" name="itens[{{ $idx }}][preco_revenda]"
                                            class="w-20 border-gray-300 rounded text-right text-xs"
                                            value="{{ old('itens.' . $idx . '.preco_revenda', number_format($item['preco_revenda'] ?? 0, 2, ',', '.')) }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center">
                    <a href="{{ route('produtos.importar_missing.form') }}"
                        class="px-3 py-2 bg-gray-200 text-gray-800 rounded shadow text-xs">
                        ← Ler outro arquivo
                    </a>

                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded shadow text-sm">
                        Cadastrar produtos selecionados
                    </button>
                </div>
            </form>
        @endif
    </div>

    @if (!empty($itens))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('btnAplicarPadrao');
                if (!btn) return;

                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const forn = document.getElementById('fornecedor_padrao')?.value || '';
                    const cat = document.getElementById('categoria_padrao')?.value || '';
                    const sub = document.getElementById('subcategoria_padrao')?.value || '';

                    if (!forn && !cat && !sub) {
                        alert(
                            'Selecione ao menos um padrão (fornecedor/categoria/subcategoria) antes de aplicar.'
                        );
                        return;
                    }

                    if (!confirm('Aplicar os padrões selecionados em todas as linhas da tabela?')) {
                        return;
                    }

                    if (forn) {
                        document.querySelectorAll('.select-fornecedor-linha').forEach(function(sel) {
                            sel.value = forn;
                        });
                    }

                    if (cat) {
                        document.querySelectorAll('.select-categoria-linha').forEach(function(sel) {
                            sel.value = cat;
                        });
                    }

                    if (sub) {
                        document.querySelectorAll('.select-subcategoria-linha').forEach(function(sel) {
                            sel.value = sub;
                        });
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
