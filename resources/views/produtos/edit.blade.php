<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Produto</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('produtos.update') }}" method="POST" enctype="multipart/form-data">


            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código Fabricante</label>
                    <input type="text" name="codfab" value="{{ $produto->codfab }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ $produto->nome }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ $produto->descricao }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                    <input type="file" name="imagem" accept="image/*" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*"
                        class="w-full border-gray-300 rounded-md shadow-sm" onchange="previewImagem(event)">

                    <div class="mt-3 flex gap-4 items-center">
                        {{-- Imagem atual --}}
                        @if($produto->imagem)
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
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ $produto->categoria_id == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Subcategoria</label>
                    <select name="subcategoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach($subcategorias as $sub)
                            <option value="{{ $sub->id }}" {{ $produto->subcategoria_id == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach($fornecedores as $for)
                            <option value="{{ $for->id }}" {{ $produto->fornecedor_id == $for->id ? 'selected' : '' }}>
                                {{ $for->nomefantasia }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $produto->status ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ !$produto->status ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('produtos.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded mr-2">Voltar</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
