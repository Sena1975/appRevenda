<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Equipe de Revenda</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('equipes.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Revendedora Responsável</label>
                    <select name="revendedora_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">-- Selecione --</option>
                        @foreach($revendedoras as $r)
                            <option value="{{ $r->id }}">{{ $r->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <a href="{{ route('equipes.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
