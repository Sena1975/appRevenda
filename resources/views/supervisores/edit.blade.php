<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Supervisor</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        <form action="{{ route('supervisores.update', $supervisor->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ $supervisor->nome }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" value="{{ $supervisor->cpf }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" value="{{ $supervisor->telefone }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ $supervisor->whatsapp }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" value="{{ $supervisor->email }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                    <input type="date" name="datanascimento" value="{{ $supervisor->datanascimento }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="cep" id="cep" value="{{ $supervisor->cep }}" class="w-full border-gray-300 rounded-md shadow-sm" onblur="buscarCEP()">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco" value="{{ $supervisor->endereco }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="{{ $supervisor->bairro }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="{{ $supervisor->cidade }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input type="text" name="estado" id="estado" value="{{ $supervisor->estado }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ $supervisor->status ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ !$supervisor->status ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <a href="{{ route('supervisores.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>

    <script>
        function buscarCEP() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        }
                    });
            }
        }
    </script>
</x-app-layout>
