{{-- resources/views/campanhas/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-700">
                Editar Campanha #{{ $campanha->id }}
            </h2>

            <a href="{{ route('campanhas.index') }}"
                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-4 space-y-4">

        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                <strong>Ops! Verifique os erros abaixo:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('campanhas.update', $campanha->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- DADOS GERAIS DA CAMPANHA --}}
            <div class="bg-white shadow rounded-lg p-4 space-y-4">
                <h3 class="text-lg font-semibold text-gray-700">
                    Dados da Campanha
                </h3>

                {{-- Nome --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nome da campanha
                    </label>
                    <input type="text" name="nome" value="{{ old('nome', $campanha->nome) }}"
                        class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Descrição
                    </label>
                    <textarea name="descricao" rows="3"
                        class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('descricao', $campanha->descricao) }}</textarea>
                </div>

                {{-- Tipo, método PHP, prioridade --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Tipo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de campanha
                        </label>
                        <select name="tipo_id"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo->id }}"
                                    {{ (int) old('tipo_id', $campanha->tipo_id) === (int) $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- método PHP --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Método PHP (regra)
                        </label>
                        <input type="text" name="metodo_php" value="{{ old('metodo_php', $campanha->metodo_php) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ex.: isCampanhaIndicacao">
                        <p class="text-xs text-gray-500 mt-1">
                            Usado internamente para aplicar a regra (ex.: <code>isCampanhaIndicacao</code>).
                        </p>
                    </div>

                    {{-- Prioridade --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Prioridade
                        </label>
                        <input type="number" name="prioridade" value="{{ old('prioridade', $campanha->prioridade) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            min="1">
                        <p class="text-xs text-gray-500 mt-1">
                            Campanhas com menor número têm prioridade maior.
                        </p>
                    </div>
                </div>

                {{-- Datas --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Data início
                        </label>
                        <input type="date" name="data_inicio"
                            value="{{ old('data_inicio', $campanha->data_inicio?->format('Y-m-d')) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Data fim
                        </label>
                        <input type="date" name="data_fim"
                            value="{{ old('data_fim', $campanha->data_fim?->format('Y-m-d')) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                {{-- Flags --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="ativa" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('ativa', $campanha->ativa) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">
                                Campanha ativa
                            </span>
                        </label>

                        <label class="inline-flex items-center">
                            <input type="checkbox" name="aplicacao_automatica" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('aplicacao_automatica', $campanha->aplicacao_automatica) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">
                                Aplicação automática
                            </span>
                        </label>

                        <label class="inline-flex items-center">
                            <input type="checkbox" name="cumulativa" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('cumulativa', $campanha->cumulativa) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">
                                Campanha cumulativa
                            </span>
                        </label>
                    </div>

                    <div class="space-y-1">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="acumulativa_por_valor" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('acumulativa_por_valor', $campanha->acumulativa_por_valor) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">
                                Acumula por valor
                            </span>
                        </label>

                        <label class="inline-flex items-center">
                            <input type="checkbox" name="acumulativa_por_quantidade" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('acumulativa_por_quantidade', $campanha->acumulativa_por_quantidade) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">
                                Acumula por quantidade
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Parâmetros específicos (desconto, cupom, brinde) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- % desc --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            % desconto (perc_desc)
                        </label>
                        <input type="number" step="0.01" name="perc_desc"
                            value="{{ old('perc_desc', $campanha->perc_desc) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ex.: 5.00">
                    </div>

                    {{-- Valor base cupom --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Valor base por cupom
                        </label>
                        <input type="number" step="0.01" name="valor_base_cupom"
                            value="{{ old('valor_base_cupom', $campanha->valor_base_cupom) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ex.: 50.00">
                    </div>

                    {{-- Quantidade mínima cupom --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Quantidade mínima por cupom
                        </label>
                        <input type="number" name="quantidade_minima_cupom"
                            value="{{ old('quantidade_minima_cupom', $campanha->quantidade_minima_cupom) }}"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ex.: 3">
                    </div>
                </div>

                {{-- Tipo de acumulação + produto brinde --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Tipo acumulação --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de acumulação
                        </label>
                        <select name="tipo_acumulacao"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- selecione --</option>
                            <option value="valor"
                                {{ old('tipo_acumulacao', $campanha->tipo_acumulacao) === 'valor' ? 'selected' : '' }}>
                                Valor
                            </option>
                            <option value="quantidade"
                                {{ old('tipo_acumulacao', $campanha->tipo_acumulacao) === 'quantidade' ? 'selected' : '' }}>
                                Quantidade
                            </option>
                        </select>
                    </div>

                    {{-- Produto brinde --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Produto brinde (opcional)
                        </label>
                        <select name="produto_brinde_id"
                            class="w-full border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- nenhum --</option>
                            @foreach ($produtos as $produto)
                                <option value="{{ $produto->id }}"
                                    {{ (int) old('produto_brinde_id', $campanha->produto_brinde_id) === (int) $produto->id ? 'selected' : '' }}>
                                    {{ $produto->nome }} @if ($produto->codfabnumero)
                                        ({{ $produto->codfabnumero }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            {{-- MENSAGENS DA CAMPANHA (WHATSAPP) --}}
            @if (!empty($eventosIndicacao))
                <div class="bg-white shadow rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Mensagens da Campanha (WhatsApp)
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Configure quais modelos de mensagem serão usados em cada evento desta campanha.
                    </p>

                    <div class="mt-4 space-y-4">
                        @foreach ($eventosIndicacao as $evento => $meta)
                            @php
                                /** @var \App\Models\CampanhaMensagem|null $config */
                                $config = $mensagensConfiguradas[$evento] ?? null;
                            @endphp

                            <div class="border rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">
                                            {{ $meta['label'] ?? $evento }}
                                        </h4>
                                        @if (!empty($meta['descricao']))
                                            <p class="text-xs text-gray-600 mt-1">
                                                {{ $meta['descricao'] }}
                                            </p>
                                        @endif

                                        @if (!empty($meta['destinatario']))
                                            <p class="text-[11px] text-gray-500 mt-1">
                                                Destinatário: <strong>{{ $meta['destinatario'] }}</strong>
                                            </p>
                                        @endif
                                    </div>

                                    <label class="inline-flex items-center text-xs text-gray-700">
                                        <input type="checkbox" name="ativo_msg[{{ $evento }}]" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            {{ old('ativo_msg.' . $evento, $config?->ativo ? 'checked' : '') ? 'checked' : '' }}>
                                        <span class="ml-2">Ativo para esta campanha</span>
                                    </label>
                                </div>

                                {{-- Seleção do modelo --}}
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Modelo de mensagem
                                    </label>
                                    <select name="mensagem_modelo_id[{{ $evento }}]"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                                       focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Não enviar mensagem para este evento --</option>

                                        @foreach ($modelosMensagem as $modelo)
                                            <option value="{{ $modelo->id }}" @selected((string) old('mensagem_modelo_id.' . $evento, $config?->mensagem_modelo_id) === (string) $modelo->id)>
                                                {{ $modelo->nome }} ({{ $modelo->codigo }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Use os modelos cadastrados em <strong>Mensageria &rarr; Modelos de
                                            Mensagens</strong>.
                                    </p>
                                </div>

                                {{-- Delay em minutos --}}
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Delay em minutos (opcional)
                                    </label>
                                    <input type="number" name="delay_minutos[{{ $evento }}]"
                                        class="block w-40 rounded-md border-gray-300 shadow-sm text-sm
                                      focus:border-indigo-500 focus:ring-indigo-500"
                                        min="0" step="1"
                                        value="{{ old('delay_minutos.' . $evento, $config?->delay_minutos ?? 0) }}">
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        0 = envia na hora. Ex.: 1440 = 24 horas após o evento.
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- CONFIGURAÇÃO DE MENSAGENS DA CAMPANHA (SOMENTE INDICAÇÃO) --}}
            @if (!empty($eventosIndicacao))
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">
                        Mensagens da Campanha
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">
                        Aqui você define quais <strong>modelos de mensagens</strong> serão usados para cada evento
                        desta campanha de indicação.
                        Se não selecionar um modelo para um evento, nenhuma mensagem automática será enviada
                        naquele momento.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 px-1">Evento</th>
                                    <th class="py-2 px-1">Modelo de mensagem</th>
                                    <th class="py-2 px-1 text-center" style="width: 110px;">Delay (min)</th>
                                    <th class="py-2 px-1 text-center" style="width: 80px;">Ativo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eventosIndicacao as $evento => $descricao)
                                    @php
                                        /** @var \App\Models\CampanhaMensagem|null $cfg */
                                        $cfg = $mensagensConfiguradas[$evento] ?? null;
                                    @endphp

                                    <tr class="border-b">
                                        <td class="py-2 px-1 align-top">
                                            <div class="font-semibold text-gray-700">
                                                {{ $descricao }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                Código do evento: <code>{{ $evento }}</code>
                                            </div>
                                        </td>

                                        <td class="py-2 px-1 align-top">
                                            <select name="mensagem_modelo_id[{{ $evento }}]"
                                                class="w-full border-gray-300 rounded text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- selecione um modelo --</option>
                                                @foreach ($modelosMensagem as $modelo)
                                                    <option value="{{ $modelo->id }}"
                                                        {{ $cfg && $cfg->mensagem_modelo_id == $modelo->id ? 'selected' : '' }}>
                                                        {{ $modelo->nome }} ({{ $modelo->codigo }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="py-2 px-1 align-top text-center">
                                            <input type="number" name="delay_minutos[{{ $evento }}]"
                                                value="{{ old('delay_minutos.' . $evento, $cfg?->delay_minutos) }}"
                                                class="w-24 border-gray-300 rounded text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="0">
                                            <div class="text-[10px] text-gray-400 mt-1">
                                                Ex.: 1440 = 24h
                                            </div>
                                        </td>

                                        <td class="py-2 px-1 align-top text-center">
                                            <input type="checkbox" name="ativo_msg[{{ $evento }}]"
                                                value="1"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                {{ old('ativo_msg.' . $evento, $cfg?->ativo ?? true) ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="text-[11px] text-gray-500 mt-3">
                        Exemplo: para o evento
                        <code>convite_indicacao_primeira_compra</code>,
                        você pode escolher um modelo de convite e definir <strong>1440</strong> minutos
                        (24h) como delay para ser disparado após o recibo da primeira compra.
                    </p>
                </div>
            @endif

            {{-- BOTÕES --}}
            <div class="flex justify-end space-x-2">
                <a href="{{ route('campanhas.index') }}"
                    class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-4 py-2 rounded text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
