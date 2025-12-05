{{-- resources/views/clientes/cadastro-publico.blade.php --}}
<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white shadow rounded-lg p-6 w-full max-w-xl">
            <h1 class="text-2xl font-bold mb-4 text-center text-gray-800">
                Cadastro de Cliente
            </h1>
            <p class="text-sm text-gray-600 mb-4 text-center">
                Preencha seus dados para que possamos te atender melhor. 💙
            </p>

            @if (isset($indicadorCliente) && $indicadorCliente)
                <div class="mb-4 p-3 rounded bg-blue-50 text-blue-800 text-sm text-center">
                    Você está se cadastrando através do link de indicação de
                    <strong>{{ $indicadorCliente->nome }}</strong>.
                </div>
            @endif


            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                    <strong>Ops! Verifique os campos abaixo:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('clientes.public.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="indicador_id" value="{{ $indicadorId ?? 1 }}">


                <div class="grid grid-cols-1 gap-4">

                    {{-- Nome --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome *</label>
                        <input type="text" name="nome" required
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('nome') }}"
                            placeholder="Seu nome completo">
                    </div>

                    {{-- WhatsApp (obrigatório) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">WhatsApp *</label>
                        <input type="text" name="whatsapp" required
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('whatsapp') }}"
                            placeholder="(xx) 9xxxx-xxxx">
                        @error('whatsapp')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- E-mail (obrigatório) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail *</label>
                        <input type="email" name="email" required
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('email') }}"
                            placeholder="voce@dominio.com">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>


                    {{-- CEP --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CEP</label>
                        <input type="text" name="cep" id="cep"
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('cep') }}"
                            placeholder="00000-000">
                        <p id="cep-msg" class="text-xs mt-1 text-gray-500"></p>
                    </div>

                    {{-- Endereço --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Endereço</label>
                        <input type="text" name="endereco" id="endereco"
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('endereco') }}"
                            placeholder="Rua, número, complemento...">
                    </div>

                    {{-- UF --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UF</label>
                        <select name="uf_id" id="uf_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Selecione</option>
                            @foreach ($ufs as $uf)
                                <option value="{{ $uf->id }}" data-sigla="{{ $uf->sigla }}"
                                    @selected(old('uf_id') == $uf->id)>
                                    {{ $uf->sigla }} - {{ $uf->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cidade --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cidade</label>
                        <select name="cidade_id" id="cidade_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            @if (old('cidade_id'))
                                <option value="{{ old('cidade_id') }}" selected>Selecionada anteriormente</option>
                            @else
                                <option value="">Selecione a UF primeiro</option>
                            @endif
                        </select>
                    </div>

                    {{-- Bairro --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bairro</label>
                        <select name="bairro_id" id="bairro_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            @if (old('bairro_id'))
                                <option value="{{ old('bairro_id') }}" selected>Selecionado anteriormente</option>
                            @else
                                <option value="">Selecione a cidade primeiro</option>
                            @endif
                        </select>
                        {{-- Hidden para bairro custom (se usar ViaCEP) --}}
                        <input type="hidden" name="bairro_nome" id="bairro_nome_hidden" value="">
                    </div>

                    {{-- Data de Nascimento --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" class="w-full border-gray-300 rounded-md shadow-sm"
                            value="{{ old('data_nascimento') }}">
                    </div>

                    {{-- Time do Coração --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time do Coração</label>

                        <select id="time_select" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Prefiro não informar</option>
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

                        {{-- Campo livre só se "Outro" --}}
                        <input type="text" id="time_outro"
                            class="mt-2 w-full border-gray-300 rounded-md shadow-sm hidden" placeholder="Digite o time"
                            autocomplete="off" value="{{ old('timecoracao') }}">

                        {{-- Campo que realmente envia --}}
                        <input type="hidden" id="time_hidden" name="timecoracao" value="{{ old('timecoracao') }}">
                        @error('timecoracao')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sexo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sexo</label>
                        <select name="sexo" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Prefiro não informar</option>
                            <option value="Feminino" @selected(old('sexo') === 'Feminino')>Feminino</option>
                            <option value="Masculino" @selected(old('sexo') === 'Masculino')>Masculino</option>
                            <option value="Outro" @selected(old('sexo') === 'Outro')>Outro</option>
                        </select>
                    </div>

                    {{-- Filhos --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Filhos</label>
                        <input type="number" name="filhos" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('filhos', 0) }}">
                    </div>

                    {{-- Foto --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opcional)</label>
                        <input type="file" name="foto" accept="image/*"
                            class="block text-sm text-gray-700 cursor-pointer">
                        <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG. Máx: 2MB</p>
                        @error('foto')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="mt-6 flex justify-center">
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md shadow hover:bg-blue-700">
                        Enviar cadastro
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- JS simples para CEP/UF/cidade/bairro (pode reaproveitar do create, resumido aqui) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cepInput = document.getElementById('cep');
            const endereco = document.getElementById('endereco');
            const msg = document.getElementById('cep-msg');
            const ufSelect = document.getElementById('uf_id');
            const cidadeSel = document.getElementById('cidade_id');
            const bairroSel = document.getElementById('bairro_id');
            const hiddenBairroNome = document.getElementById('bairro_nome_hidden');

            const timeSelect = document.getElementById('time_select');
            const timeOutro = document.getElementById('time_outro');
            const timeHidden = document.getElementById('time_hidden');

            function atualizarTime() {
                const val = timeSelect.value;
                if (val === '__OUTRO__') {
                    timeOutro.classList.remove('hidden');
                    timeOutro.focus();
                    timeHidden.value = timeOutro.value.trim();
                } else {
                    timeOutro.classList.add('hidden');
                    timeOutro.value = '';
                    timeHidden.value = val || '';
                }
            }

            timeSelect.addEventListener('change', atualizarTime);
            timeOutro.addEventListener('input', () => {
                timeHidden.value = timeOutro.value.trim();
            });

            // Garante valor certo ao enviar
            const form = document.querySelector('form');
            form.addEventListener('submit', () => {
                atualizarTime();
            });

            // se já tiver valor antigo (old), força estado inicial
            if (timeHidden.value) {
                const jaSelecionado = [...timeSelect.options].find(o => o.value === timeHidden.value);
                if (jaSelecionado) {
                    timeSelect.value = timeHidden.value;
                } else {
                    timeSelect.value = '__OUTRO__';
                    timeOutro.classList.remove('hidden');
                    timeOutro.value = timeHidden.value;
                }
            }


            // máscara simples
            cepInput.addEventListener('input', function() {
                let v = this.value.replace(/\D/g, '');
                this.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5, 8) : v;
            });


            cepInput.addEventListener('blur', async function() {
                const cep = this.value.replace(/\D/g, '');
                msg.textContent = '';
                msg.className = 'text-xs mt-1 text-gray-500';
                hiddenBairroNome.value = '';

                if (cep.length !== 8) {
                    msg.textContent = 'CEP inválido.';
                    msg.classList.add('text-red-600');
                    return;
                }

                try {
                    console.log('Consultando CEP:', cep);

                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);

                    if (!res.ok) {
                        console.error('Erro HTTP ViaCEP:', res.status);
                        msg.textContent = 'Erro ao consultar CEP (HTTP).';
                        msg.classList.add('text-red-600');
                        return;
                    }

                    const data = await res.json();
                    console.log('Resposta ViaCEP:', data);

                    if (data.erro) {
                        msg.textContent = `CEP não encontrado no ViaCEP.`;
                        msg.classList.add('text-red-600');
                        return;
                    }

                    endereco.value = data.logradouro || '';
                    const bairroNome = (data.bairro || '').trim();
                    const cidadeNome = (data.localidade || '').trim();
                    const ufSigla = (data.uf || '').trim();

                    // seleciona UF
                    const ufOpt = [...ufSelect.options].find(o =>
                        (o.dataset.sigla || '').toUpperCase() === ufSigla.toUpperCase()
                    );
                    if (!ufOpt) {
                        msg.textContent = 'UF não cadastrada no sistema.';
                        msg.classList.add('text-red-600');
                        return;
                    }
                    ufSelect.value = ufOpt.value;

                    // carrega cidades
                    const cidades = await fetch(`/get-cidades/${ufOpt.value}`).then(r => r.json());
                    cidadeSel.innerHTML = '<option value="">Selecione</option>';
                    let cidadeEncontrada = null;
                    cidades.forEach(c => {
                        const sel = c.nome.trim().toLowerCase() === cidadeNome.toLowerCase();
                        cidadeSel.insertAdjacentHTML('beforeend',
                            `<option value="${c.id}" ${sel ? 'selected' : ''}>${c.nome}</option>`
                        );
                        if (sel) cidadeEncontrada = c;
                    });

                    // carrega bairros
                    if (cidadeEncontrada) {
                        const bairros = await fetch(`/get-bairros/${cidadeEncontrada.id}`).then(r => r
                            .json());
                        bairroSel.innerHTML = '<option value="">Selecione</option>';
                        let marcou = false;
                        bairros.forEach(b => {
                            const sel = b.nome.trim().toLowerCase() === bairroNome
                                .toLowerCase() && bairroNome !== '';
                            bairroSel.insertAdjacentHTML('beforeend',
                                `<option value="${b.id}" ${sel ? 'selected' : ''}>${b.nome}</option>`
                            );
                            if (sel) marcou = true;
                        });

                        if (!marcou && bairroNome) {
                            bairroSel.insertAdjacentHTML('beforeend',
                                `<option value="custom" selected>${bairroNome}</option>`);
                            hiddenBairroNome.value = bairroNome;
                        }
                    }

                    msg.textContent = 'Endereço preenchido com sucesso.';
                    msg.classList.add('text-green-600');
                } catch (e) {
                    msg.textContent = 'Erro ao consultar CEP.';
                    msg.classList.add('text-red-600');
                }
            });

            ufSelect.addEventListener('change', async function() {
                cidadeSel.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-cidades/${this.value}`).then(r => r.json());
                cidadeSel.innerHTML = '<option value="">Selecione</option>';
                data.forEach(c => cidadeSel.insertAdjacentHTML('beforeend',
                    `<option value="${c.id}">${c.nome}</option>`));
                bairroSel.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                hiddenBairroNome.value = '';
            });

            cidadeSel.addEventListener('change', async function() {
                bairroSel.innerHTML = '<option>Carregando...</option>';
                const data = await fetch(`/get-bairros/${this.value}`).then(r => r.json());
                bairroSel.innerHTML = '<option value="">Selecione</option>';
                data.forEach(b => bairroSel.insertAdjacentHTML('beforeend',
                    `<option value="${b.id}">${b.nome}</option>`));
                hiddenBairroNome.value = '';
            });

            bairroSel.addEventListener('change', function() {
                if (this.value !== 'custom') {
                    hiddenBairroNome.value = '';
                }
            });
        });
    </script>
</x-guest-layout>
