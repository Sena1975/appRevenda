{{-- resources/views/clientes/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Cliente</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- campo oculto para suportar "bairro custom" (preenchido via JS quando necessário) --}}
            <input type="hidden" name="bairro_nome" id="bairro_nome_hidden" value="">

            <div class="grid grid-cols-2 gap-4">

                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="000.000.000-00">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="(xx) xxxx-xxxx">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="(xx) 9xxxx-xxxx">
                </div>

                <!-- Telegram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telegram</label>
                    <input type="text" name="telegram" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="@usuario">
                </div>

                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="@usuario">
                </div>

                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="facebook.com/usuario">
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
                        <input type="text" name="cep" id="cep"
                            class="w-full border-gray-300 rounded-md shadow-sm pr-10" placeholder="00000-000">
                        <div id="cep-loader"
                            class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-blue-500 animate-spin">⏳</div>
                    </div>
                    <p id="cep-msg" class="text-xs mt-1"></p>
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco"
                        class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Rua, avenida, número...">
                </div>

                <!-- UF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">UF</label>
                    <select name="uf_id" id="uf_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach (DB::table('appuf')->orderBy('nome')->get() as $uf)
                            <option value="{{ $uf->id }}" data-sigla="{{ $uf->sigla }}">{{ $uf->sigla }} -
                                {{ $uf->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <select name="cidade_id" id="cidade_id" class="w-full border-gray-300 rounded-md shadow-sm"
                        required>
                        <option value="">Selecione a UF primeiro</option>
                    </select>
                </div>

                <!-- Bairro -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <select name="bairro_id" id="bairro_id" class="w-full border-gray-300 rounded-md shadow-sm"
                        required>
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

                    {{-- Select com principais clubes + "Outro" --}}
                    <select id="time_select" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione</option>
                        <optgroup label="Sudeste">
                            <option>Flamengo</option>
                            <option>Vasco</option>
                            <option>Botafogo</option>
                            <option>Fluminense</option>
                            <option>Corinthians</option>
                            <option>Palmeiras</option>
                            <option>Santos</option>
                            <option>São Paulo</option>
                            <option>Cruzeiro</option>
                            <option>Atlético Mineiro</option>
                        </optgroup>
                        <optgroup label="Sul">
                            <option>Grêmio</option>
                            <option>Internacional</option>
                            <option>Athletico Paranaense</option>
                            <option>Coritiba</option>
                        </optgroup>
                        <optgroup label="Nordeste">
                            <option>Bahia</option>
                            <option>Vitória</option>
                            <option>Sport</option>
                            <option>Náutico</option>
                            <option>Santa Cruz</option>
                            <option>Fortaleza</option>
                            <option>Ceará</option>
                        </optgroup>
                        <optgroup label="Centro-Oeste e Norte">
                            <option>Goiás</option>
                            <option>Atlético Goianiense</option>
                            <option>Remo</option>
                            <option>Paysandu</option>
                            <option>Cuiabá</option>
                        </optgroup>
                        <option value="__OUTRO__">Outro</option>
                    </select>

                    {{-- Campo livre só aparece se escolher "Outro" --}}
                    <input type="text" id="time_outro"
                        class="mt-2 w-full border-gray-300 rounded-md shadow-sm hidden" placeholder="Digite o time"
                        autocomplete="off">

                    {{-- Campo que realmente envia (trocamos o name via JS) --}}
                    <input type="hidden" id="time_hidden" name="timecoracao" value="">
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
                    <input type="number" name="filhos" min="0"
                        class="w-full border-gray-300 rounded-md shadow-sm" value="0">
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
                        <img id="foto-preview" src="{{ asset('storage/clientes/default.png') }}" alt="Preview"
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
                <a href="{{ route('clientes.index') }}"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</a>
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Script único (CEP + UF/Cidade/Bairro + Foto preview + bairro custom) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ufSelect = document.getElementById('uf_id');
            const cidadeSelect = document.getElementById('cidade_id');
            const bairroSelect = document.getElementById('bairro_id');
            const cepInput = document.getElementById('cep');
            const enderecoInput = document.getElementById('endereco');
            const loader = document.getElementById('cep-loader');
            const msg = document.getElementById('cep-msg');

            const hiddenBairroNome = document.getElementById('bairro_nome_hidden');

 // --- Time do Coração (select + "Outro") ---
const timeSelect = document.getElementById('time_select');
const timeOutro  = document.getElementById('time_outro');
const timeHidden = document.getElementById('time_hidden');

// estado inicial
timeHidden.value = '';

function atualizarTime() {
    const val = timeSelect.value;
    if (val === '__OUTRO__') {
        timeOutro.classList.remove('hidden');
        timeOutro.focus();
        timeHidden.value = timeOutro.value.trim(); // se já tiver digitado
    } else {
        timeOutro.classList.add('hidden');
        timeOutro.value = '';
        timeHidden.value = val || '';
    }
}

timeSelect.addEventListener('change', atualizarTime);
timeOutro.addEventListener('input', () => {
    // enquanto digita no "Outro", mantemos em timeHidden para enviar
    timeHidden.value = timeOutro.value.trim();
});

// garante que, ao enviar o form, o valor correto está em timeHidden
const form = document.querySelector('form');
form.addEventListener('submit', () => {
    atualizarTime();
});
           
            // Pré-visualização da foto
            const inputFoto = document.getElementById('foto');
            const previewFoto = document.getElementById('foto-preview');
            if (inputFoto && previewFoto) {
                inputFoto.addEventListener('change', function() {
                    const file = this.files?.[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = e => previewFoto.src = e.target.result;
                    reader.readAsDataURL(file);
                });
            }

            // Máscara simples do CEP
            cepInput.addEventListener('input', function() {
                let v = this.value.replace(/\D/g, '');
                this.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5, 8) : v;
            });

            // Busca ViaCEP ao sair do campo
            cepInput.addEventListener('blur', async function() {
                const cep = this.value.replace(/\D/g, '');
                msg.textContent = '';
                msg.className = 'text-xs mt-1 text-gray-600';
                loader.classList.remove('hidden');

                // Sempre limpa o hidden do bairro custom ao iniciar nova busca
                hiddenBairroNome.value = '';

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
                    const bairroNome = (data.bairro || '').trim();
                    const cidadeNome = (data.localidade || '').trim();
                    const ufSigla = (data.uf || '').trim();

                    // Seleciona a UF pelo data-sigla
                    const ufOption = [...ufSelect.options].find(o => (o.dataset.sigla || '')
                        .toUpperCase() === ufSigla.toUpperCase());
                    if (!ufOption) {
                        msg.textContent = '⚠️ UF não encontrada no sistema.';
                        msg.classList.add('text-red-600');
                        return;
                    }

                    ufSelect.value = ufOption.value;

                    // Carrega cidades da UF
                    const cidades = await fetch(`/get-cidades/${ufOption.value}`).then(r => r.json());
                    cidadeSelect.innerHTML = '<option value="">Selecione</option>';

                    let cidadeEncontrada = null;
                    cidades.forEach(c => {
                        const match = c.nome.trim().toLowerCase() === cidadeNome.toLowerCase();
                        cidadeSelect.insertAdjacentHTML('beforeend',
                            `<option value="${c.id}" ${match ? 'selected' : ''}>${c.nome}</option>`
                            );
                        if (match) cidadeEncontrada = c;
                    });

                    // Carrega bairros da cidade (se encontrada)
                    if (cidadeEncontrada) {
                        const bairros = await fetch(`/get-bairros/${cidadeEncontrada.id}`).then(r => r
                            .json());
                        bairroSelect.innerHTML = '<option value="">Selecione</option>';

                        let marcou = false;
                        bairros.forEach(b => {
                            const selected = b.nome.trim().toLowerCase() === bairroNome
                                .toLowerCase() && bairroNome !== '';
                            bairroSelect.insertAdjacentHTML('beforeend',
                                `<option value="${b.id}" ${selected ? 'selected' : ''}>${b.nome}</option>`
                                );
                            if (selected) marcou = true;
                        });

                        // Se não achou, cria opção "custom" e guarda o nome no hidden
                        if (!marcou && bairroNome) {
                            bairroSelect.insertAdjacentHTML('beforeend',
                                `<option value="custom" selected>${bairroNome}</option>`);
                            hiddenBairroNome.value = bairroNome; // <- envia no POST
                        } else {
                            hiddenBairroNome.value = ''; // garante limpeza
                        }
                    }

                    msg.textContent = '✅ Endereço preenchido com sucesso.';
                    msg.classList.add('text-green-600');
                } catch (e) {
                    loader.classList.add('hidden');
                    msg.textContent = '❌ Erro ao consultar o CEP.';
                    msg.classList.add('text-red-600');
                }
            });

            // UF → Cidades
            ufSelect.addEventListener('change', async function() {
                cidadeSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-cidades/${this.value}`).then(r => r.json());
                cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(c => cidadeSelect.insertAdjacentHTML('beforeend',
                    `<option value="${c.id}">${c.nome}</option>`));

                // reset de bairros e do custom
                bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                hiddenBairroNome.value = '';
            });

            // Cidade → Bairros
            cidadeSelect.addEventListener('change', async function() {
                bairroSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-bairros/${this.value}`).then(r => r.json());
                bairroSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(b => bairroSelect.insertAdjacentHTML('beforeend',
                    `<option value="${b.id}">${b.nome}</option>`));

                // ao mudar de cidade, limpa o custom
                hiddenBairroNome.value = '';
            });

            // Quando o usuário mexer manualmente no select de bairro:
            bairroSelect.addEventListener('change', function() {
                // Se escolheu "custom", mantém o hidden (valor deve estar definido por CEP)
                if (this.value !== 'custom') {
                    // Qualquer bairro diferente de custom limpa o hidden
                    hiddenBairroNome.value = '';
                }
            });
        });
    </script>
</x-app-layout>
