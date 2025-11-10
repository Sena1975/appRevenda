@props([
    // 'contexto' => 'compra' | 'venda'  (define qual preço usar)
    'contexto' => 'venda',

    // IDs/CSS selectors dos campos do formulário que serão preenchidos:
    'idCodigo' => '#codigo_fabrica',
    'idDescricao' => '#descricao_produto',
    'idPreco' => '#preco_unitario',
    'idEstoque' => '#qtd_estoque',
    'idPontos' => '#pontos',
    'idCiclo' => '#ciclo',
    'idDataUltima' => '#data_ultima_entrada',
])

<div
    x-data="produtoPicker({
        contexto: '{{ $contexto }}',
        idCodigo: '{{ $idCodigo }}',
        idDescricao: '{{ $idDescricao }}',
        idPreco: '{{ $idPreco }}',
        idEstoque: '{{ $idEstoque }}',
        idPontos: '{{ $idPontos }}',
        idCiclo: '{{ $idCiclo }}',
        idDataUltima: '{{ $idDataUltima }}',
    })"
    class="relative"
>
    <label class="block text-sm font-medium text-gray-700 mb-1">Produto</label>
    <input
        type="text"
        x-model="q"
        @input.debounce.300ms="buscar()"
        @keydown.arrow-down.prevent="moverSelecao(1)"
        @keydown.arrow-up.prevent="moverSelecao(-1)"
        @keydown.enter.prevent="confirmarSelecao()"
        placeholder="Digite parte do nome ou código..."
        class="w-full border-gray-300 rounded-md shadow-sm"
    />

    <!-- Dropdown -->
    <template x-if="aberto">
        <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-64 overflow-auto">
            <template x-if="carregando">
                <div class="p-3 text-sm text-gray-500">Carregando...</div>
            </template>

            <template x-if="!carregando && resultados.length === 0">
                <div class="p-3 text-sm text-gray-500">Nenhum resultado.</div>
            </template>

            <template x-for="(item, idx) in resultados" :key="item.id">
                <button type="button"
                        @click="selecionar(idx)"
                        :class="idx === foco ? 'bg-gray-100' : ''"
                        class="w-full text-left px-3 py-2 hover:bg-gray-100">
                    <div class="text-sm font-medium" x-text="item.text"></div>
                    <div class="text-xs text-gray-500">
                        <span x-text="'Estoque: ' + item.qtd_estoque"></span> ·
                        <span x-text="'Pontos: ' + item.pontos"></span> ·
                        <span x-text="'Compra: R$ ' + (item.preco_compra ?? 0).toFixed(2)"></span> ·
                        <span x-text="'Revenda: R$ ' + (item.preco_revenda ?? 0).toFixed(2)"></span>
                    </div>
                </button>
            </template>
        </div>
    </template>
</div>

<!-- Script do componente -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('produtoPicker', (opts) => ({
        q: '',
        aberto: false,
        carregando: false,
        resultados: [],
        foco: -1,
        contexto: opts.contexto || 'venda',
        ids: {
            codigo: opts.idCodigo,
            descricao: opts.idDescricao,
            preco: opts.idPreco,
            estoque: opts.idEstoque,
            pontos: opts.idPontos,
            ciclo: opts.idCiclo,
            dataUltima: opts.idDataUltima,
        },

        async buscar() {
            this.foco = -1;
            const termo = this.q.trim();
            if (termo.length < 2) {
                this.resultados = [];
                this.aberto = false;
                return;
            }
            this.carregando = true;
            this.aberto = true;
            try {
                const url = `/api/produtos/buscar?q=${encodeURIComponent(termo)}&limit=20`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.resultados = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error(e);
                this.resultados = [];
            } finally {
                this.carregando = false;
            }
        },

        moverSelecao(delta) {
            if (!this.aberto || this.resultados.length === 0) return;
            const max = this.resultados.length - 1;
            let novo = this.foco + delta;
            if (novo < 0) novo = 0;
            if (novo > max) novo = max;
            this.foco = novo;
        },

        confirmarSelecao() {
            if (this.foco >= 0) this.selecionar(this.foco);
        },

        selecionar(idx) {
            const p = this.resultados[idx];
            if (!p) return;

            // Preenche campos do form
            this.setValue(this.ids.codigo, p.codigo_fabrica || '');
            this.setValue(this.ids.descricao, p.descricao || '');

            // Define o preço conforme o contexto
            const preco = this.contexto === 'compra'
                ? (p.preco_compra ?? 0)
                : (p.preco_revenda ?? 0);

            this.setValue(this.ids.preco, (Number(preco) || 0).toFixed(2));
            this.setValue(this.ids.estoque, p.qtd_estoque ?? 0);
            this.setValue(this.ids.pontos, p.pontos ?? 0);
            this.setValue(this.ids.ciclo, p.ciclo ?? '');
            this.setValue(this.ids.dataUltima, p.data_ultima_entrada ?? '');

            // Exibe no campo de busca o escolhido
            this.q = `${p.descricao} (${p.codigo_fabrica})`;
            this.aberto = false;

            // Dispara evento para quem quiser recalcular total etc.
            window.dispatchEvent(new CustomEvent('produto-selecionado', { detail: { produto: p, contexto: this.contexto } }));
        },

        setValue(selector, value) {
            try {
                const el = document.querySelector(selector);
                if (el) el.value = value;
            } catch(_) {}
        },
    }))
});
</script>
