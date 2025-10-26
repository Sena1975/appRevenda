<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Produto</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('produtos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código Fabricante</label>
                    <input type="text" name="codfab" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*" 
                        class="w-full border-gray-300 rounded-md shadow-sm" onchange="previewImagem(event)">
                    
                <div class="mt-3">
                    <div class="border border-gray-300 rounded-md flex items-center justify-center overflow-hidden shadow"
                        style="width: 100px; height: 100px;">
                        <img id="preview" src="#" alt="Pré-visualização"
                            class="hidden object-cover w-full h-full rounded-md">
                    </div>
                </div>


                </div>

                <script>
                function previewImagem(event) {
                    const [file] = event.target.files;
                    const preview = document.getElementById('preview');
                    
                    if (file) {
                        preview.src = URL.createObjectURL(file);
                        preview.classList.remove('hidden');
                    } else {
                        preview.classList.add('hidden');
                        preview.src = "#";
                    }
                }
                </script>


                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Subcategoria</label>
                    <select name="subcategoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach($subcategorias as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach($fornecedores as $for)
                            <option value="{{ $for->id }}">{{ $for->nomefantasia }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

<div class="mt-6 flex justify-end items-center gap-2">
    {{-- Cancelar (forçando cor) --}}
    <a href="{{ route('produtos.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg shadow transition-all duration-150"
       style="background-color:#4B5563; color:#FFFFFF;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#F472B6;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>Cancelar</span>
    </a>

    {{-- Salvar (forçando cor) --}}
    <button type="submit"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg shadow transition-all duration-150"
        style="background-color:#2563EB; color:#FFFFFF;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Salvar</span>
    </button>
</div>


        </form>
    </div>
</x-app-layout>
