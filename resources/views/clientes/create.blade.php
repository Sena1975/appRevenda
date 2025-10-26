<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Cliente</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-2 gap-4">

                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="000.000.000-00">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="(xx) xxxx-xxxx">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="(xx) 9xxxx-xxxx">
                </div>

                <!-- Telegram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telegram</label>
                    <input type="text" name="telegram" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="@usuario">
                </div>

                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="@usuario">
                </div>

                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="facebook.com/usuario">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- CEP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <div class="relative">
                        <input type="text" name="cep" id="cep" class="w-full border-gray-300 rounded-md shadow-sm pr-10" placeholder="00000-000">
                        <div id="cep-loader" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-blue-500 animate-spin">⏳</div>
                    </div>
                    <p id="cep-msg" class="text-xs mt-1"></p>
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Rua, avenida, número...">
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

                <!-- Data de Nascimento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Time do Coração -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time do Coração</label>
                    <input type="text" name="timecoracao" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Ex: Corinthians">
                </div>

                <!-- Sexo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sexo</label>
                    <select name="sexo" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <!-- Filhos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Filhos</label>
                    <input type="number" name="filhos" min="0" class="w-full border-gray-300 rounded-md shadow-sm" value="0">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="Ativo">Ativo</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>

            <!-- Foto com preview -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                
                <div class="flex items-center gap-4">
                    <!-- Imagem de pré-visualização -->
                <img id="foto-preview"
                    src="{{ asset('storage/clientes/default.png') }}"
                    alt="Preview"
                    class="w-24 h-24 object-cover rounded-full border border-gray-300 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-md">

                        <div>
                            <input type="file" name="foto" id="foto" accept="image/*"
                                class="block text-sm text-gray-700 cursor-pointer">
                            <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG. Máx: 2MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="mt-6 flex justify-end gap-4">
                <a href="{{ route('clientes.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Script AJAX otimizado -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ufSelect = document.getElementById('uf_id');
            const cidadeSelect = document.getElementById('cidade_id');
            const bairroSelect = document.getElementById('bairro_id');
            const cepInput = document.getElementById('cep');
            const enderecoInput = document.getElementById('endereco');
            const loader = document.getElementById('cep-loader');
            const msg = document.getElementById('cep-msg');

            // 🔹 Formata CEP
            cepInput.addEventListener('input', function () {
                let v = this.value.replace(/\D/g, '');
                this.value = v.length > 5 ? v.slice(0,5)+'-'+v.slice(5,8) : v;
            });

            // 🔹 Busca ViaCEP
            cepInput.addEventListener('blur', async function () {
                const cep = this.value.replace(/\D/g, '');
                msg.textContent = ''; loader.classList.remove('hidden');
                msg.className = 'text-xs mt-1 text-gray-600';

                if (cep.length !== 8) {
                    loader.classList.add('hidden');
                    msg.textContent = '⚠️ CEP inválido.';
                    msg.classList.add('text-red-600');
                    return;
                }

                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await res.json();

                    loader.classList.add('hidden');

                    if (data.erro) {
                        msg.textContent = '❌ CEP não encontrado.';
                        msg.classList.add('text-red-600');
                        return;
                    }

                    enderecoInput.value = data.logradouro || '';
                    const bairroNome = data.bairro || '';

                    // Define UF
                    const ufOption = [...ufSelect.options].find(o => o.dataset.sigla === data.uf);
                    if (ufOption) {
                        ufSelect.value = ufOption.value;

                        const cidades = await fetch(`/get-cidades/${ufOption.value}`).then(r => r.json());
                        cidadeSelect.innerHTML = '<option value="">Selecione</option>';

                        let cidadeEncontrada = null;
                        cidades.forEach(c => {
                            const match = c.nome.trim().toLowerCase() === data.localidade.trim().toLowerCase();
                            cidadeSelect.innerHTML += `<option value="${c.id}" ${match ? 'selected' : ''}>${c.nome}</option>`;
                            if (match) cidadeEncontrada = c;
                        });

                    if (cidadeEncontrada) {
                        const bairros = await fetch(`/get-bairros/${cidadeEncontrada.id}`).then(r => r.json());
                        bairroSelect.innerHTML = '<option value="">Selecione</option>';

                        if (bairros.length > 0) {
                            bairros.forEach(b => {
                                const selected = b.nome.trim().toLowerCase() === bairroNome.trim().toLowerCase() ? 'selected' : '';
                                bairroSelect.innerHTML += `<option value="${b.id}" ${selected}>${b.nome}</option>`;
                            });
                        } else if (bairroNome) {
                            // fallback — insere o bairro vindo do ViaCEP
                            bairroSelect.innerHTML += `<option value="custom">${bairroNome}</option>`;
                            bairroSelect.value = 'custom';
                        }
                    }


                        msg.textContent = '✅ Endereço preenchido com sucesso.';
                        msg.classList.add('text-green-600');
                    } else {
                        msg.textContent = '⚠️ UF não encontrada no sistema.';
                        msg.classList.add('text-red-600');
                    }
                } catch {
                    loader.classList.add('hidden');
                    msg.textContent = '❌ Erro ao consultar o CEP.';
                    msg.classList.add('text-red-600');
                }
            });

            // 🔹 UF → Cidades
            ufSelect.addEventListener('change', async function () {
                cidadeSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-cidades/${this.value}`).then(r => r.json());
                cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(c => cidadeSelect.innerHTML += `<option value="${c.id}">${c.nome}</option>`);
                bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
            });

            // 🔹 Cidade → Bairros
            cidadeSelect.addEventListener('change', async function () {
                bairroSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-bairros/${this.value}`).then(r => r.json());
                bairroSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(b => bairroSelect.innerHTML += `<option value="${b.id}">${b.nome}</option>`);
            });
        });


        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('foto');
            const preview = document.getElementById('foto-preview');

            input.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
            });
        });
        </script>



    </script>

</x-app-layout>
