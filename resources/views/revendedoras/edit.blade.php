<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Revendedora</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('revendedoras.update', $revendedora->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome', $revendedora->nome) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" value="{{ old('cpf', $revendedora->cpf) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" value="{{ old('telefone', $revendedora->telefone) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $revendedora->whatsapp) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $revendedora->email) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" value="{{ old('endereco', $revendedora->endereco) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Cidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="cidade" value="{{ old('cidade', $revendedora->cidade) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input type="text" name="estado" maxlength="2" value="{{ old('estado', $revendedora->estado) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $revendedora->status ? 'selected' : '' }}>Ativa</option>
                        <option value="0" {{ !$revendedora->status ? 'selected' : '' }}>Inativa</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
    <input type="checkbox" id="revenda_padrao" name="revenda_padrao" value="1"
           @checked(old('revenda_padrao', $revendedora->revenda_padrao))
           class="h-4 w-4 border-gray-300 rounded">
    <label for="revenda_padrao" class="text-sm text-gray-700">
        Definir como Revendedora Padrão
    </label>
</div>

            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <a href="{{ route('revendedoras.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
