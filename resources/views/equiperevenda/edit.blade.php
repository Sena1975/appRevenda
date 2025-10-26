<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Equipe de Revenda</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('equiperevenda.update', $equipe->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome', $equipe->nome) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- Revendedora -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Revendedora Responsável</label>
                    <select name="revendedora_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">-- Selecione --</option>
                        @foreach($revendedoras as $r)
                            <option value="{{ $r->id }}" {{ $r->id == $equipe->revendedora_id ? 'selected' : '' }}>
                                {{ $r->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Descrição -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('descricao', $equipe->descricao) }}</textarea>
                </div>

                <!-- Status -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $equipe->status ? 'selected' : '' }}>Ativa</option>
                        <option value="0" {{ !$equipe->status ? 'selected' : '' }}>Inativa</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <a href="{{ route('equiperevenda.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
