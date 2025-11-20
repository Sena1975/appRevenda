{{-- resources/views/clientes/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Cliente</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- campo oculto para suportar "bairro custom" (preenchido via JS quando necessário) --}}
            <input type="hidden" name="bairro_nome" id="bairro_nome_hidden" value="">

            <div class="grid grid-cols-2 gap-4">

                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('nome', $cliente->nome) }}" required>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('cpf', $cliente->cpf) }}" placeholder="000.000.000-00">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('telefone', $cliente->telefone) }}" placeholder="(xx) xxxx-xxxx">
                </div>

                {{-- WhatsApp --}}
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" id="whatsapp"
                        value="{{ old('whatsapp', $cliente->whatsapp ?? '') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="(71) 99999-9999">
                    @error('whatsapp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('instagram', $cliente->instagram) }}" placeholder="@usuario ou link do perfil"
                        autocomplete="off">
                    @error('instagram')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telegram --}}
                <div>
                    <label for="telegram" class="block text-sm font-medium text-gray-700">Telegram</label>
                    <input type="text" name="telegram" id="telegram"
                        value="{{ old('telegram', isset($cliente) ? '@' . $cliente->telegram : (old('telegram') ? '@' . ltrim(old('telegram'), '@') : '')) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="@usuario">
                    @error('telegram')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>


                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('facebook', $cliente->facebook) }}" placeholder="facebook.com/usuario">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" id="email" inputmode="email" autocomplete="email"
                        class="w-full border-gray-300 rounded-md shadow-sm" value="{{ old('email', $cliente->email) }}"
                        placeholder="nome@dominio.com" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$">
                    <small class="text-xs text-gray-500">Use um e-mail válido, ex: nome@dominio.com</small>
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>



                <!-- CEP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <div class="relative">
                        <input type="text" name="cep" id="cep"
                            class="w-full border-gray-300 rounded-md shadow-sm pr-10"
                            value="{{ old('cep', $cliente->cep) }}" placeholder="00000-000">
                        <div id="cep-loader"
                            class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-blue-500 animate-spin">⏳</div>
                    </div>
                    <p id="cep-msg" class="text-xs mt-1"></p>
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco"
                        class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('endereco', $cliente->endereco) }}" placeholder="Rua, avenida, número...">
                </div>

                <!-- UF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">UF</label>
                    <select name="uf_id" id="uf_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione</option>
                        @foreach (DB::table('appuf')->orderBy('nome')->get() as $uf)
                            <option value="{{ $uf->id }}" data-sigla="{{ $uf->sigla }}">{{ $uf->sigla }}
                                - {{ $uf->nome }}</option>
                        @endforeach
                    </select>
                    <small class="text-xs text-gray-500">Atual: {{ $cliente->uf ?: '—' }}</small>
                </div>

                <!-- Cidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <select name="cidade_id" id="cidade_id" class="w-full border-gray-300 rounded-md shadow-sm"
                        required>
                        {{-- opções serão carregadas via JS; exibimos a atual só como placeholder inicial --}}
                        @if ($cliente->cidade)
                            <option value="__keep__" selected>{{ $cliente->cidade }}</option>
                        @else
                            <option value="">Selecione a UF primeiro</option>
                        @endif
                    </select>
                    <small class="text-xs text-gray-500">Atual: {{ $cliente->cidade ?: '—' }}</small>
                </div>

                <!-- Bairro -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <select name="bairro_id" id="bairro_id" class="w-full border-gray-300 rounded-md shadow-sm"
                        required>
                        {{-- opções serão carregadas via JS; exibimos o atual como placeholder inicial --}}
                        @if ($cliente->bairro)
                            <option value="__keep__" selected>{{ $cliente->bairro }}</option>
                        @else
                            <option value="">Selecione a cidade primeiro</option>
                        @endif
                    </select>
                    <small class="text-xs text-gray-500">Atual: {{ $cliente->bairro ?: '—' }}</small>
                </div>

                <!-- Data de Nascimento -->
                @php
                    $valData = old('data_nascimento');

                    if (is_null($valData)) {
                        $raw = $cliente->data_nascimento;

                        if ($raw instanceof \Carbon\Carbon) {
                            // cast 'date' retorna Carbon
                            $valData = $raw->format('Y-m-d');
                        } elseif (is_string($raw)) {
                            $raw = trim($raw);

                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                                // já está no formato aceito
                                $valData = $raw;
                            } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                                // está em dd/mm/yyyy -> converte
                                try {
                                    $valData = \Carbon\Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    $valData = '';
                                }
                            } else {
                                $valData = '';
                            }
                        } else {
                            $valData = '';
                        }
                    }
                @endphp

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ $valData }}">
                    @error('data_nascimento')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sexo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sexo</label>
                    <select name="sexo" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione</option>
                        <option value="Masculino" @selected(old('sexo', $cliente->sexo) === 'Masculino')>Masculino</option>
                        <option value="Feminino" @selected(old('sexo', $cliente->sexo) === 'Feminino')>Feminino</option>
                        <option value="Outro" @selected(old('sexo', $cliente->sexo) === 'Outro')>Outro</option>
                    </select>
                </div>

                <!-- Filhos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Filhos</label>
                    <input type="number" name="filhos" min="0"
                        class="w-full border-gray-300 rounded-md shadow-sm"
                        value="{{ old('filhos', $cliente->filhos ?? 0) }}">
                </div>

                <!-- Time do Coração (select + "Outro") -->
                @php
                    $timesLista = [
                        'Sudeste' => [
                            'Flamengo',
                            'Vasco',
                            'Botafogo',
                            'Fluminense',
                            'Corinthians',
                            'Palmeiras',
                            'Santos',
                            'São Paulo',
                            'Cruzeiro',
                            'Atlético Mineiro',
                        ],
                        'Sul' => ['Grêmio', 'Internacional', 'Athletico Paranaense', 'Coritiba'],
                        'Nordeste' => ['Bahia', 'Vitória', 'Sport', 'Náutico', 'Santa Cruz', 'Fortaleza', 'Ceará'],
                        'Centro-Oeste e Norte' => ['Goiás', 'Atlético Goianiense', 'Remo', 'Paysandu', 'Cuiabá'],
                    ];
                    $valorTime = old('timecoracao', $cliente->timecoracao);
                    // se o time salvo não estiver na lista, vamos marcar "__OUTRO__" e exibir campo texto
                    $estaNaLista = false;
                    foreach ($timesLista as $grupo => $arr) {
                        if (in_array($valorTime, $arr)) {
                            $estaNaLista = true;
                            break;
                        }
                    }
                @endphp

                <div>
                    <label class="block text-sm font-medium text-gray-700">Time do Coração</label>
                    <select id="time_select" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Selecione</option>
                        @foreach ($timesLista as $grupo => $arr)
                            <optgroup label="{{ $grupo }}">
                                @foreach ($arr as $t)
                                    <option value="{{ $t }}" @selected($estaNaLista && $valorTime === $t)>
                                        {{ $t }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        <option value="__OUTRO__" @selected(!$estaNaLista && $valorTime)>Outro</option>
                    </select>

                    <input type="text" id="time_outro"
                        class="mt-2 w-full border-gray-300 rounded-md shadow-sm {{ $estaNaLista ? 'hidden' : '' }}"
                        placeholder="Digite o time" autocomplete="off" value="{{ $estaNaLista ? '' : $valorTime }}">

                    <input type="hidden" id="time_hidden" name="timecoracao" value="{{ $valorTime }}">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="Ativo" @selected(old('status', $cliente->status) === 'Ativo')>Ativo</option>
                        <option value="Inativo" @selected(old('status', $cliente->status) === 'Inativo')>Inativo</option>
                    </select>
                </div>

                <!-- Foto com preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                    <div class="flex items-center gap-4">
                        <img id="foto-preview"
                            src="{{ $cliente->foto ? asset('storage/' . $cliente->foto) : asset('storage/clientes/default.png') }}"
                            alt="Preview"
                            class="w-24 h-24 object-cover rounded-full border border-gray-300 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-md">
                        <div>
                            <input type="file" name="foto" id="foto" accept="image/*"
                                class="block text-sm text-gray-700 cursor-pointer">
                            <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG. Máx: 2MB</p>
                        </div>
                    </div>
                </div>
<div>
    <label class="block text-sm font-medium text-gray-700">Origem do cadastro</label>
    <select name="origem_cadastro" class="w-full border-gray-300 rounded-md shadow-sm">
        @php
            $origemOld = old('origem_cadastro', $cliente->origem_cadastro ?? 'Interno');
        @endphp
        <option value="Interno" @selected($origemOld === 'Interno')>Interno</option>
        <option value="Cadastro Público" @selected($origemOld === 'Cadastro Público')>Cadastro Público</option>
        <option value="Importação" @selected($origemOld === 'Importação')>Importação</option>
        <option value="WhatsApp" @selected($origemOld === 'WhatsApp')>WhatsApp</option>
        <option value="Instagram" @selected($origemOld === 'Instagram')>Instagram</option>
    </select>
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

    <!-- Script único (CEP + UF/Cidade/Bairro + Foto preview + bairro custom + Time com "Outro") -->
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const ufSelect = document.getElementById('uf_id');
            const cidadeSelect = document.getElementById('cidade_id');
            const bairroSelect = document.getElementById('bairro_id');
            const cepInput = document.getElementById('cep');
            const enderecoInput = document.getElementById('endereco');
            const loader = document.getElementById('cep-loader');
            const msg = document.getElementById('cep-msg');
            const hiddenBairroNome = document.getElementById('bairro_nome_hidden');

            const inputFoto = document.getElementById('foto');
            const previewFoto = document.getElementById('foto-preview');

            const timeSelect = document.getElementById('time_select');
            const timeOutro = document.getElementById('time_outro');
            const timeHidden = document.getElementById('time_hidden');

            // --- Foto preview ---
            if (inputFoto && previewFoto) {
                inputFoto.addEventListener('change', function() {
                    const file = this.files?.[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = e => previewFoto.src = e.target.result;
                    reader.readAsDataURL(file);
                });
            }

            // --- CEP máscara ---
            if (cepInput) {
                let v = cepInput.value?.replace(/\D/g, '') || '';
                if (v.length === 8) cepInput.value = v.slice(0, 5) + '-' + v.slice(5, 8);
                cepInput.addEventListener('input', function() {
                    let val = this.value.replace(/\D/g, '');
                    this.value = val.length > 5 ? val.slice(0, 5) + '-' + val.slice(5, 8) : val;
                });
            }

            // --- Pre-seleção de UF/Cidade/Bairro com base no cliente atual ---
            const ufAtualSigla = @json($cliente->uf);
            const cidadeAtualNome = @json($cliente->cidade);
            const bairroAtualNome = @json($cliente->bairro);

            async function carregarCidades(ufId, selecionarPorNome = null) {
                const cidades = await fetch(`/get-cidades/${ufId}`).then(r => r.json());
                cidadeSelect.innerHTML = '<option value="">Selecione</option>';
                let cidadeEscolhida = null;
                cidades.forEach(c => {
                    const match = selecionarPorNome && c.nome.trim().toLowerCase() ===
                        selecionarPorNome.trim().toLowerCase();
                    cidadeSelect.insertAdjacentHTML('beforeend',
                        `<option value="${c.id}" ${match ? 'selected' : ''}>${c.nome}</option>`);
                    if (match) cidadeEscolhida = c;
                });
                return cidadeEscolhida;
            }

            async function carregarBairros(cidadeId, selecionarPorNome = null) {
                const bairros = await fetch(`/get-bairros/${cidadeId}`).then(r => r.json());
                bairroSelect.innerHTML = '<option value="">Selecione</option>';
                let marcou = false;
                bairros.forEach(b => {
                    const match = selecionarPorNome && b.nome.trim().toLowerCase() ===
                        selecionarPorNome.trim().toLowerCase();
                    bairroSelect.insertAdjacentHTML('beforeend',
                        `<option value="${b.id}" ${match ? 'selected' : ''}>${b.nome}</option>`);
                    if (match) marcou = true;
                });
                if (!marcou && selecionarPorNome) {
                    // cria "custom"
                    bairroSelect.insertAdjacentHTML('beforeend',
                        `<option value="custom" selected>${selecionarPorNome}</option>`);
                    hiddenBairroNome.value = selecionarPorNome;
                } else {
                    hiddenBairroNome.value = '';
                }
            }

            // Seleciona UF pela sigla atual, carrega cidades e bairros
            if (ufAtualSigla) {
                const ufOption = [...ufSelect.options].find(o => (o.dataset.sigla || '').toUpperCase() ===
                    ufAtualSigla.toUpperCase());
                if (ufOption) {
                    ufSelect.value = ufOption.value;
                    const cidadeEscolhida = await carregarCidades(ufOption.value, cidadeAtualNome);
                    if (cidadeEscolhida) {
                        await carregarBairros(cidadeEscolhida.id, bairroAtualNome);
                    } else {
                        // se não achou cidade, limpa bairros
                        bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                    }
                }
            }

            // --- CEP busca (igual ao create) ---
            cepInput?.addEventListener('blur', async function() {
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

                    const ufOption = [...ufSelect.options].find(o => (o.dataset.sigla || '')
                        .toUpperCase() === ufSigla.toUpperCase());
                    if (!ufOption) {
                        msg.textContent = '⚠️ UF não encontrada no sistema.';
                        msg.classList.add('text-red-600');
                        return;
                    }
                    ufSelect.value = ufOption.value;

                    const cidadeEscolhida = await carregarCidades(ufOption.value, cidadeNome);
                    if (cidadeEscolhida) {
                        await carregarBairros(cidadeEscolhida.id, bairroNome);
                    }

                    msg.textContent = '✅ Endereço preenchido com sucesso.';
                    msg.classList.add('text-green-600');
                } catch (e) {
                    loader.classList.add('hidden');
                    msg.textContent = '❌ Erro ao consultar o CEP.';
                    msg.classList.add('text-red-600');
                }
            });

            // --- UF → Cidades
            ufSelect.addEventListener('change', async function() {
                const cidadeEscolhida = await carregarCidades(this.value, null);
                bairroSelect.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
                hiddenBairroNome.value = '';
            });

            // --- Cidade → Bairros
            cidadeSelect.addEventListener('change', async function() {
                await carregarBairros(this.value, null);
                hiddenBairroNome.value = '';
            });

            // --- Bairro manual
            bairroSelect.addEventListener('change', function() {
                if (this.value !== 'custom') hiddenBairroNome.value = '';
            });

            // --- Time do Coração (select + "Outro") ---
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
            // estado inicial já está no Blade; apenas garante consistência
            atualizarTime();
            timeSelect.addEventListener('change', atualizarTime);
            timeOutro.addEventListener('input', () => {
                timeHidden.value = timeOutro.value.trim();
            });

            // garante consistência no submit
            const form = document.querySelector('form');
            form.addEventListener('submit', () => {
                atualizarTime();
            });
        });
    </script>
</x-app-layout>
