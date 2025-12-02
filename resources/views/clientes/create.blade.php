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

            <div class="space-y-6">

                {{-- BLOCO: Dados básicos --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Dados básicos</h3>

                    {{-- Nome + CPF --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nome -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('nome') }}" required>
                            @error('nome')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- CPF -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CPF</label>
                            <input type="text" name="cpf" class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('cpf') }}" placeholder="000.000.000-00">
                            @error('cpf')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Indicador --}}
                    <div>
                        <label for="indicador_id" class="block text-sm font-medium text-gray-700">
                            Indicador
                        </label>

                        @php
                            $valorIndicador = old('indicador_id', 1);
                        @endphp

                        <select name="indicador_id" id="indicador_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            {{-- Opção padrão: vendedor / sem indicação --}}
                            <option value="1" {{ $valorIndicador == 1 ? 'selected' : '' }}>
                                ID-1 – Vendedor (sem prêmio de indicação)
                            </option>

                            @foreach ($indicadores as $ind)
                                <option value="{{ $ind->id }}" {{ $valorIndicador == $ind->id ? 'selected' : '' }}>
                                    ID-{{ $ind->id }} – {{ $ind->nome }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-1 text-xs text-gray-500 leading-snug">
                            Se este cliente foi indicado por outro, selecione aqui o cliente indicador.
                            Caso contrário, deixe como “Vendedor”.
                        </p>
                    </div>
                </div>

                {{-- BLOCO: Contatos --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Contatos</h3>

                    {{-- Telefone + WhatsApp --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Telefone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" name="telefone" class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('telefone') }}" placeholder="(xx) xxxx-xxxx">
                            @error('telefone')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- WhatsApp --}}
                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                            <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="(71) 99999-9999">
                            @error('whatsapp')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 leading-snug">
                                Informe o WhatsApp principal do cliente para contato e envio de mensagens.
                            </p>
                        </div>
                    </div>

                    {{-- Telegram --}}
                    <div>
                        <label for="telegram" class="block text-sm font-medium text-gray-700">Telegram (opcional)</label>
                        <input type="text" name="telegram" id="telegram"
                               value="{{ old('telegram') ? '@' . ltrim(old('telegram'), '@') : '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="@usuario">
                        @error('telegram')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 leading-snug">
                            Se o cliente usar Telegram, informe o @username para contato opcional.
                        </p>
                    </div>

                    {{-- Instagram + Facebook --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Instagram -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Instagram</label>
                            <input type="text" name="instagram" class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('instagram') }}" placeholder="@usuario">
                            @error('instagram')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Facebook -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Facebook</label>
                            <input type="text" name="facebook" class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('facebook') }}">
                            @error('facebook')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="email" id="email" inputmode="email" autocomplete="email"
                               class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('email') }}"
                               placeholder="nome@dominio.com" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$">
                        @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1 leading-snug">
                            O e-mail será utilizado para comunicações e recuperação de acesso, se necessário.
                        </p>
                    </div>
                </div>

                {{-- BLOCO: Endereço --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Endereço</h3>

                    {{-- CEP + Endereço --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- CEP -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <div class="relative">
                                <input type="text" name="cep" id="cep"
                                       class="w-full border-gray-300 rounded-md shadow-sm pr-10" value="{{ old('cep') }}"
                                       placeholder="00000-000">
                                <span id="cep-loader"
                                      class="hidden absolute inset-y-0 right-2 flex items-center text-gray-400 text-xs">
                                    Buscando...
                                </span>
                            </div>
                            <p id="cep-msg" class="text-xs mt-1 text-gray-600"></p>
                            @error('cep')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Endereço -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Endereço</label>
                            <input type="text" name="endereco" id="endereco"
                                   class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('endereco') }}"
                                   placeholder="Rua, avenida, número...">
                            @error('endereco')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- UF + Cidade + Bairro --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- UF -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">UF</label>
                            <select name="uf_id" id="uf_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Selecione</option>
                                @foreach (DB::table('appuf')->orderBy('nome')->get() as $uf)
                                    <option value="{{ $uf->id }}" data-sigla="{{ $uf->sigla }}"
                                            @selected(old('uf_id') == $uf->id)>
                                        {{ $uf->sigla }} - {{ $uf->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('uf_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cidade -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cidade</label>
                            <select name="cidade_id" id="cidade_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @if (old('cidade_id'))
                                    <option value="{{ old('cidade_id') }}" selected>Selecionada anteriormente</option>
                                @else
                                    <option value="">Selecione a UF primeiro</option>
                                @endif
                            </select>
                            @error('cidade_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bairro -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <select name="bairro_id" id="bairro_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @if (old('bairro_id'))
                                    <option value="{{ old('bairro_id') }}" selected>Selecionado anteriormente</option>
                                @else
                                    <option value="">Selecione a cidade primeiro</option>
                                @endif
                            </select>
                            @error('bairro_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- BLOCO: Perfil --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Perfil</h3>

                    {{-- Data de nascimento + Sexo --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Data de Nascimento -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                            <input type="date" name="data_nascimento"
                                   class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('data_nascimento') }}">
                            @error('data_nascimento')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sexo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sexo</label>
                            <select name="sexo" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Selecione</option>
                                <option value="Masculino" @selected(old('sexo') === 'Masculino')>Masculino</option>
                                <option value="Feminino" @selected(old('sexo') === 'Feminino')>Feminino</option>
                                <option value="Outro" @selected(old('sexo') === 'Outro')>Outro</option>
                            </select>
                            @error('sexo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Time do Coração --}}
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
                                <option>Atlético Paranaense</option>
                                <option>Coritiba</option>
                            </optgroup>
                            <optgroup label="Outros">
                                <option>Bahia</option>
                                <option>Vitória</option>
                                <option>Sport</option>
                                <option>Santa Cruz</option>
                                <option>Náutico</option>
                            </optgroup>
                            <option value="__OUTRO__">Outro (digite)</option>
                        </select>

                        {{-- Campo de texto para "Outro" (inicialmente escondido) --}}
                        <input type="text" id="time_outro"
                               class="mt-2 w-full border-gray-300 rounded-md shadow-sm hidden" autocomplete="off"
                               value="{{ old('timecoracao') }}">

                        {{-- Campo que realmente envia --}}
                        <input type="hidden" id="time_hidden" name="timecoracao" value="{{ old('timecoracao') }}">
                        @error('timecoracao')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Estado civil + Possui filhos + Quantidade --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Estado Civil -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado Civil</label>
                            <select name="estado_civil" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Selecione</option>
                                <option value="Solteiro(a)" @selected(old('estado_civil') === 'Solteiro(a)')>Solteiro(a)</option>
                                <option value="Casado(a)" @selected(old('estado_civil') === 'Casado(a)')>Casado(a)</option>
                                <option value="Divorciado(a)" @selected(old('estado_civil') === 'Divorciado(a)')>Divorciado(a)</option>
                                <option value="Viúvo(a)" @selected(old('estado_civil') === 'Viúvo(a)')>Viúvo(a)</option>
                                <option value="União Estável" @selected(old('estado_civil') === 'União Estável')>União Estável</option>
                            </select>
                            @error('estado_civil')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Possui filhos -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Possui filhos?</label>
                            <select name="possui_filhos" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Selecione</option>
                                <option value="Sim" @selected(old('possui_filhos') === 'Sim')>Sim</option>
                                <option value="Não" @selected(old('possui_filhos') === 'Não')>Não</option>
                            </select>
                            @error('possui_filhos')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Quantidade de filhos -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade de filhos</label>
                            <input type="number" name="filhos" min="0"
                                   class="w-full border-gray-300 rounded-md shadow-sm"
                                   value="{{ old('filhos', 0) }}">
                            @error('filhos')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- BLOCO: Foto e Origem --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Foto e origem</h3>

                    <!-- Foto do cliente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto do cliente (opcional)</label>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                <img id="foto-preview" src="{{ asset('images/default-avatar.png') }}" alt="Foto do cliente"
                                     class="w-full h-full object-cover">
                            </div>
                            <div>
                                <input type="file" name="foto" id="foto" accept="image/*"
                                       class="text-sm text-gray-700">
                                <p class="text-xs text-gray-500 mt-1 leading-snug">
                                    Formatos aceitos: JPG, PNG. Tamanho máximo recomendado: 2MB.
                                </p>
                            </div>
                        </div>
                        @error('foto')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Origem do cadastro --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Origem do cadastro</label>
                        <select name="origem_cadastro" class="w-full border-gray-300 rounded-md shadow-sm">
                            @php
                                $origemOld = old('origem_cadastro', 'Interno');
                            @endphp
                            <option value="Interno" @selected($origemOld === 'Interno')>Interno</option>
                            <option value="Cadastro Público" @selected($origemOld === 'Cadastro Público')>Cadastro Público</option>
                            <option value="Importação" @selected($origemOld === 'Importação')>Importação</option>
                            <option value="WhatsApp" @selected($origemOld === 'WhatsApp')>WhatsApp</option>
                            <option value="Instagram" @selected($origemOld === 'Instagram')>Instagram</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="mt-6 flex justify-end gap-4">
                <a href="{{ route('clientes.index') }}"
                   class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</a>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <!-- Script único (CEP + UF/Cidade/Bairro + Foto preview + bairro custom + Time do Coração) -->
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

            // --- Time do Coração ---
            const timeSelect = document.getElementById('time_select');
            const timeOutro = document.getElementById('time_outro');
            const timeHidden = document.getElementById('time_hidden');

            // estado inicial
            timeHidden.value = timeOutro.value.trim();

            function atualizarTime() {
                const val = timeSelect.value;

                if (val === '__OUTRO__') {
                    timeOutro.classList.remove('hidden');
                    timeOutro.focus();
                    timeHidden.value = timeOutro.value.trim();
                } else if (val) {
                    timeOutro.classList.add('hidden');
                    timeOutro.value = '';
                    timeHidden.value = val;
                } else {
                    // select vazio → mantém valor atual (ex: vindo de old)
                }
            }

            timeSelect.addEventListener('change', atualizarTime);
            timeOutro.addEventListener('input', () => {
                timeHidden.value = timeOutro.value.trim();
            });

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

                hiddenBairroNome.value = '';

                if (cep.length !== 8) {
                    loader.classList.add('hidden');
                    msg.textContent = '⚠️ CEP inválido.';
                    msg.classList.add('text-red-600');
                    return;
                }

                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();

                    loader.classList.add('hidden');

                    if (data.erro) {
                        msg.textContent = 'CEP não encontrado.';
                        msg.classList.add('text-red-600');
                        return;
                    }

                    enderecoInput.value = data.logradouro || '';

                    const ufOption = Array.from(ufSelect.options).find(
                        opt => opt.dataset.sigla === data.uf
                    );
                    if (ufOption) {
                        ufOption.selected = true;
                    }

                    msg.textContent = `Endereço encontrado: ${data.logradouro || ''} - ${data.bairro || ''}, ${
                        data.localidade || ''
                    }/${data.uf || ''}`;

                    if (ufSelect.value) {
                        const cidadesResp = await fetch(`/get-cidades/${ufSelect.value}`);
                        const cidades = await cidadesResp.json();

                        cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                        let cidadeSelecionada = null;

                        cidades.forEach(c => {
                            const isCidade = c.nome.toLowerCase() === (data.localidade || '').toLowerCase();
                            const optHtml = `<option value="${c.id}" ${isCidade ? 'selected' : ''}>${c.nome}</option>`;
                            cidadeSelect.insertAdjacentHTML('beforeend', optHtml);
                            if (isCidade) cidadeSelecionada = c.id;
                        });

                        if (cidadeSelecionada) {
                            const bairrosResp = await fetch(`/get-bairros/${cidadeSelecionada}`);
                            const bairros = await bairrosResp.json();

                            bairroSelect.innerHTML = '<option value="">Selecione</option>';

                            let bairroEncontrado = false;

                            bairros.forEach(b => {
                                if (b.nome.toLowerCase() === (data.bairro || '').toLowerCase()) {
                                    bairroSelect.insertAdjacentHTML(
                                        'beforeend',
                                        `<option value="${b.id}" selected>${b.nome}</option>`
                                    );
                                    bairroEncontrado = true;
                                } else {
                                    bairroSelect.insertAdjacentHTML(
                                        'beforeend',
                                        `<option value="${b.id}">${b.nome}</option>`
                                    );
                                }
                            });

                            if (!bairroEncontrado && data.bairro) {
                                const customOption = document.createElement('option');
                                customOption.value = 'custom';
                                customOption.textContent = `${data.bairro} (outro)`;
                                customOption.selected = true;
                                bairroSelect.appendChild(customOption);

                                hiddenBairroNome.value = data.bairro;
                            }
                        } else {
                            bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                        }
                    }
                } catch (error) {
                    loader.classList.add('hidden');
                    console.error(error);
                    msg.textContent = '❌ Erro ao consultar o CEP.';
                    msg.classList.add('text-red-600');
                }
            });

            // UF → Cidades
            ufSelect.addEventListener('change', async function() {
                const ufId = this.value;

                cidadeSelect.innerHTML = '<option value="">Selecione a UF primeiro</option>';
                bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                hiddenBairroNome.value = '';

                if (!ufId) {
                    return;
                }

                cidadeSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-cidades/${ufId}`).then(r => r.json());
                cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(c => cidadeSelect.insertAdjacentHTML('beforeend',
                    `<option value="${c.id}">${c.nome}</option>`));
            });

            // Cidade → Bairros
            cidadeSelect.addEventListener('change', async function() {
                const cidadeId = this.value;

                bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                hiddenBairroNome.value = '';

                if (!cidadeId) {
                    return;
                }

                bairroSelect.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-bairros/${cidadeId}`).then(r => r.json());
                bairroSelect.innerHTML = '<option value="">Selecione</option>';
                data.forEach(b => bairroSelect.insertAdjacentHTML('beforeend',
                    `<option value="${b.id}">${b.nome}</option>`));
            });

            // Bairros: quando usuário muda manualmente
            bairroSelect.addEventListener('change', function() {
                if (this.value !== 'custom') {
                    hiddenBairroNome.value = '';
                }
            });
        });
    </script>
</x-app-layout>
