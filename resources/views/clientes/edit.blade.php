<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Cliente</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
        <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome', $cliente->nome) }}" 
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $cliente->email) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" value="{{ old('telefone', $cliente->telefone) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Data de nascimento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="{{ old('data_nascimento', $cliente->data_nascimento) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- CEP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <div class="relative">
                        <input type="text" name="cep" id="cep"
                            class="w-full border-gray-300 rounded-md shadow-sm pr-10"
                            placeholder="00000-000">
                        <!-- Spinner de carregamento -->
                        <div id="cep-loader" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 animate-spin">
                            ⏳
                        </div>
                    </div>
                    <p id="cep-msg" class="text-xs mt-1"></p>
                </div>


                <!-- UF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">UF</label>
                    <select name="uf_id" id="uf_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach(DB::table('appuf')->orderBy('nome')->get() as $uf)
                            <option value="{{ $uf->id }}" data-sigla="{{ $uf->sigla }}">{{ $uf->sigla }} - {{ $uf->nome }}</option>
                        @endforeach
                    </select>
                </div>


                <!-- Cidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <select name="cidade_id" id="cidade_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione a UF primeiro</option>
                    </select>
                </div>

                <!-- Bairro -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <select name="bairro_id" id="bairro_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione a cidade primeiro</option>
                    </select>
                </div>

                <!-- Time do Coração -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time do Coração</label>
                    <input type="text" name="timecoracao" value="{{ old('timecoracao', $cliente->timecoracao) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Foto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Foto</label>
                    <input type="file" name="foto" class="w-full text-sm text-gray-700">
                    @if ($cliente->foto)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $cliente->foto) }}" alt="Foto" 
                                 class="w-16 h-16 rounded-full object-cover">
                        </div>
                    @endif
                </div>

                <!-- Sexo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sexo</label>
                    <select name="sexo" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione</option>
                        <option value="Masculino" {{ old('sexo', $cliente->sexo) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                        <option value="Feminino" {{ old('sexo', $cliente->sexo) == 'Feminino' ? 'selected' : '' }}>Feminino</option>
                    </select>
                </div>

                <!-- Filhos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Filhos</label>
                    <input type="number" name="filhos" min="0" 
                           value="{{ old('filhos', $cliente->filhos) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <a href="{{ route('clientes.index') }}"
                   class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</a>

                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    Atualizar
                </button>
            </div>
        </form>
    </div>

    <!-- Script AJAX para carregar cidades e bairros -->
    <script>
        const ufSelect = document.getElementById('uf_id');
        const cidadeSelect = document.getElementById('cidade_id');
        const bairroSelect = document.getElementById('bairro_id');

        const clienteUf = "{{ $cliente->uf_id ?? '' }}";
        const clienteCidade = "{{ $cliente->cidade_id ?? '' }}";
        const clienteBairro = "{{ $cliente->bairro_id ?? '' }}";

        // Função para carregar cidades da UF
        function carregarCidades(ufId, cidadeSelecionada = null) {
            cidadeSelect.innerHTML = '<option value="">Carregando...</option>';
            bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';

            fetch(`/get-cidades/${ufId}`)
                .then(res => res.json())
                .then(data => {
                    cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                    data.forEach(cidade => {
                        const selected = cidadeSelecionada == cidade.id ? 'selected' : '';
                        cidadeSelect.innerHTML += `<option value="${cidade.id}" ${selected}>${cidade.nome}</option>`;
                    });

                    // Carrega bairros se já houver cidade selecionada
                    if (cidadeSelecionada) carregarBairros(cidadeSelecionada, clienteBairro);
                });
        }

        // Função para carregar bairros da cidade
        function carregarBairros(cidadeId, bairroSelecionado = null) {
            bairroSelect.innerHTML = '<option value="">Carregando...</option>';
            fetch(`/get-bairros/${cidadeId}`)
                .then(res => res.json())
                .then(data => {
                    bairroSelect.innerHTML = '<option value="">Selecione</option>';
                    data.forEach(bairro => {
                        const selected = bairroSelecionado == bairro.id ? 'selected' : '';
                        bairroSelect.innerHTML += `<option value="${bairro.id}" ${selected}>${bairro.nome}</option>`;
                    });
                });
        }

        // Eventos de mudança
        ufSelect.addEventListener('change', () => {
            if (ufSelect.value) carregarCidades(ufSelect.value);
        });

        cidadeSelect.addEventListener('change', () => {
            if (cidadeSelect.value) carregarBairros(cidadeSelect.value);
        });

        // Carrega automaticamente os dados do cliente
        document.addEventListener('DOMContentLoaded', () => {
            if (clienteUf) {
                carregarCidades(clienteUf, clienteCidade);
            }
        });
    </script>
</x-app-layout>
