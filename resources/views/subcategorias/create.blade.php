{{-- resources/views/subcategorias/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Nova Subcategoria</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-red-800">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('subcategorias.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nome da Subcategoria</label>
                    <input type="text" name="nome" value="{{ old('nome') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required maxlength="100">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('categoria_id')===(string)$cat->id?'selected':'' }}>
                                {{ $cat->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Texto livre</label>
                    <input type="text" name="subcategoria" value="{{ old('subcategoria') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('status','1')==='1'?'selected':'' }}>Ativo</option>
                        <option value="0" {{ old('status')==='0'?'selected':'' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('subcategorias.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">Cancelar</a>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
