<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Revendedora</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
        <form action="{{ route('revendedoras.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
@if ($errors->any())
  <div class="mb-3 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
      <ul class="list-disc list-inside">
          @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
          @endforeach
      </ul>
  </div>
@endif

            <div class="grid grid-cols-2 gap-4">
                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" value="{{ old('cpf') }}" maxlength="14" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CEP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="cep" id="cep" maxlength="9" class="w-full border-gray-300 rounded-md shadow-sm" onblur="buscarCep(this.value)">
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Bairro -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="bairro" id="bairro" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Cidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="cidade" id="cidade" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input type="text" name="estado" id="estado" maxlength="2" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" value="{{ old('telefone') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
<div class="flex items-center gap-2">
    <input type="checkbox" id="revenda_padrao" name="revenda_padrao" value="1"
           class="h-4 w-4 border-gray-300 rounded">
    <label for="revenda_padrao" class="text-sm text-gray-700">
        Definir como Revendedora Padrão
    </label>
</div>

                <!-- Email -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <a href="{{ route('revendedoras.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Script para buscar CEP -->
    <script>
        function buscarCep(cep) {
            cep = cep.replace(/\D/g, '');
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
                    })
                    .catch(() => alert('Erro ao buscar o CEP'));
            }
        }
    </script>
</x-app-layout>
