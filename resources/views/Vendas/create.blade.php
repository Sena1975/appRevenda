{{-- resources/views/vendas/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Novo Pedido de Venda</h1>

@if(session('error'))
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
@endif
@if(session('success'))
  <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
@endif
@if(session('info'))
  <div class="mb-3 p-3 bg-blue-100 text-blue-800 rounded">{{ session('info') }}</div>
@endif


    @if($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('vendas.store') }}" method="POST" id="formVenda">
        @csrf

        {{-- DADOS PRINCIPAIS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium mb-1">Cliente *</label>
                <select name="cliente_id" class="w-full border rounded" required>
                    <option value="">Selecione...</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Revendedora</label>
                <select name="revendedora_id" class="w-full border rounded">
                    <option value="">(Opcional)</option>
                    @foreach($revendedoras as $r)
                        <option value="{{ $r->id }}">{{ $r->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Forma de Pagamento *</label>
                <select name="forma_pagamento_id" id="formaPagamento" class="w-full border rounded" required>
                    <option value="">Selecione...</option>
                    @foreach($formas as $f)
                        <option value="{{ $f->id }}">{{ $f->nome ?? $f->descricao ?? ('Forma #'.$f->id) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Plano de Pagamento *</label>
                <select name="plano_pagamento_id" id="planoPagamento" class="w-full border rounded" required>
                    <option value="">Selecione a forma primeiro...</option>
                </select>
                <input type="hidden" name="codplano" id="codplano">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Data do Pedido</label>
                <input type="date" name="data_pedido" class="w-full border rounded" value="{{ date('Y-m-d') }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Previsão de Entrega</label>
                <input type="date" name="previsao_entrega" class="w-full border rounded">
            </div>
        </div>

        {{-- RESUMO RÁPIDO --}}
        <div class="flex flex-wrap gap-4 items-center mb-2 text-sm">
            <span>Itens: <strong id="contadorItens">1</strong></span>
            <span>Total de Pontos: <strong id="totalPontos">0</strong></span>
        </div>

        {{-- ITENS --}}
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Itens do Pedido</h2>
            <button type="button" id="btnAdd" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Adicionar item</button>
        </div>

        <div class="overflow-x-auto mb-4">
            <table class="min-w-full border table-fixed" id="tblItens">
                <colgroup>
                    <col style="width: 60%">
                    <col style="width: 8%">
                    <col style="width: 8%">
                    <col style="width: 10%">
                    <col style="width: 10%">
                    <col style="width: 6rem">
                </colgroup>
                <thead class="bg-gray-50 text-sm">
                    <tr>
                        <th class="px-2 py-2 text-left w-[900px]">Produto (CODFAB - Nome)</th>
                        <th class="px-2 py-2 text-right w-24">Qtd</th>
                        <th class="px-2 py-2 text-right w-24">Pontos</th>
                        <th class="px-2 py-2 text-right w-32">R$ Unit</th>
                        <th class="px-2 py-2 text-right w-32">R$ Total</th>
                        <th class="px-2 py-2 text-center w-16">Ação</th>
                    </tr>
                </thead>
                <tbody id="linhas">
                    {{-- Linha inicial (índice 0) --}}
                    <tr class="linha border-t">
                        <td class="px-2 py-2">
                            <input type="hidden" name="itens[0][produto_id]" class="produto-id-hidden">
                            <input type="hidden" name="itens[0][codfabnumero]" class="codfab-hidden">
                            <input type="hidden" name="itens[0][pontuacao]" class="pontos-unit-hidden">          {{-- NOVO --}}
                            <input type="hidden" name="itens[0][pontuacao_total]" class="pontos-total-hidden">   {{-- NOVO --}}

                            <select class="produtoSelect w-full border rounded" required>
                                <option value="">Selecione...</option>
                                @foreach($produtos as $p)
                                    <option
                                        value="{{ $p->id }}"
                                        data-codfab="{{ $p->codfabnumero }}"
                                        data-nome="{{ $p->nome }}"
                                    >
                                        {{ $p->codfabnumero }} - {{ $p->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="1" step="1" value="1" name="itens[0][quantidade]"
                                   class="quantidade w-full border rounded text-right" inputmode="numeric" pattern="\d*">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="0" step="1" class="pontos-unit w-full border rounded text-right" readonly>
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="0" step="0.01" name="itens[0][preco_unitario]" class="preco-unit w-full border rounded text-right">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="0" step="0.01" name="itens[0][preco_total]" class="preco-total w-full border rounded text-right" readonly>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <button type="button"
                                    class="btnDel inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold
                                        rounded-md bg-red-600 text-white hover:bg-red-700
                                        focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Excluir
                            </button>
                        </td>

                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="4" class="px-2 py-2 text-right font-semibold">Total Bruto (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_total" id="totalBruto" class="w-full border rounded text-right" readonly>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-2 py-2 text-right">Desconto (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_desconto" id="totalDesc" class="w-full border rounded text-right" value="0">
                        </td>
                        <td></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="4" class="px-2 py-2 text-right font-semibold">Total Líquido (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_liquido" id="totalLiq" class="w-full border rounded text-right" readonly>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- HIDDEN do pedido (pontuações e código do plano) --}}
        <input type="hidden" name="pontuacao" id="pedidoPontuacao">
        <input type="hidden" name="pontuacao_total" id="pedidoPontuacaoTotal">

        {{-- OBSERVAÇÃO --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Observação</label>
            <textarea name="observacao" rows="3" class="w-full border rounded"></textarea>
        </div>

{{-- Barra de ações fixa no rodapé --}}
<div class="sticky bottom-0 left-0 right-0 mt-4">
    <div class="bg-red/95 backdrop-blur border-t px-4 py-3 rounded-t">
        <div class="max-w-6xl mx-auto flex items-center justify-end gap-2">
            <a href="{{ route('vendas.index') }}"
               class="px-3 py-2 rounded-md border text-gray-700 hover:bg-red-50">
               Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold
                           hover:bg-green-700 focus:outline-none focus:ring-2
                           focus:ring-green-500 focus:ring-offset-2">
                Salvar
            </button>
        </div>
    </div>
</div>




    </form>
</div>

{{-- TEMPLATE DE NOVA LINHA --}}
<template id="tplLinha">
<tr class="linha border-t">
    <td class="px-2 py-2">
        <input type="hidden" class="produto-id-hidden">
        <input type="hidden" class="codfab-hidden">
        <input type="hidden" class="pontos-unit-hidden">     {{-- NOVO --}}
        <input type="hidden" class="pontos-total-hidden">    {{-- NOVO --}}
        <select class="produtoSelect w-full border rounded" required>
            <option value="">Selecione...</option>
            @foreach($produtos as $p)
                <option
                    value="{{ $p->id }}"
                    data-codfab="{{ $p->codfabnumero }}"
                    data-nome="{{ $p->nome }}"
                >
                    {{ $p->codfabnumero }} - {{ $p->nome }}
                </option>
            @endforeach
        </select>
    </td>
    <td class="px-2 py-2">
        <input type="number" min="1" step="1" value="1" class="quantidade w-full border rounded text-right" inputmode="numeric" pattern="\d*">
    </td>
    <td class="px-2 py-2">
        <input type="number" min="0" step="1" class="pontos-unit w-full border rounded text-right" readonly>
    </td>
    <td class="px-2 py-2">
        <input type="number" min="0" step="0.01" class="preco-unit w-full border rounded text-right">
    </td>
    <td class="px-2 py-2">
        <input type="number" min="0" step="0.01" class="preco-total w-full border rounded text-right" readonly>
    </td>
    <td class="px-2 py-2 text-center">
        <button type="button" class="btnDel px-2 py-1 bg-red-500 text-white rounded text-xs">X</button>
    </td>
</tr>
</template>

{{-- URL base (rota nomeada) para carregar planos por forma --}}
<script>
    const URL_PLANOS_BASE = @json(route('planopagamento.getByForma', ['forma_id' => '__FORMA__']));
</script>

{{-- SCRIPT PRINCIPAL --}}
<script>
(function(){
    const tbody          = document.getElementById('linhas');
    const tpl            = document.getElementById('tplLinha');
    const btnAdd         = document.getElementById('btnAdd');

    const totalBruto     = document.getElementById('totalBruto');
    const totalDesc      = document.getElementById('totalDesc');
    const totalLiq       = document.getElementById('totalLiq');
    const contadorItens  = document.getElementById('contadorItens');
    const totalPontos    = document.getElementById('totalPontos');

    const formaPagamento = document.getElementById('formaPagamento');
    const planoPagamento = document.getElementById('planoPagamento');
    const codplanoHidden = document.getElementById('codplano');

    function toN(v){
      if (v == null) return 0;
      let s = String(v).trim();
      if (s.includes('.') && s.includes(',')) {
        s = s.replace(/\./g, '').replace(',', '.');
      } else if (s.includes(',') && !s.includes('.')) {
        s = s.replace(',', '.');
      }
      const n = parseFloat(s);
      return isNaN(n) ? 0 : n;
    }

    function getQtdInt(tr) {
        let v = parseInt((tr.querySelector('.quantidade')?.value || '1'), 10);
        if (isNaN(v) || v < 1) v = 1;
        return v;
    }

    function renomear(){
        [...tbody.querySelectorAll('tr.linha')].forEach((tr, idx) => {
            const pid = tr.querySelector('.produto-id-hidden');
            const cod = tr.querySelector('.codfab-hidden');
            const q   = tr.querySelector('.quantidade');
            const pu  = tr.querySelector('.preco-unit');
            const pt  = tr.querySelector('.preco-total');
            const pUh = tr.querySelector('.pontos-unit-hidden');
            const pTh = tr.querySelector('.pontos-total-hidden');

            if (pid) pid.name = `itens[${idx}][produto_id]`;
            if (cod) cod.name = `itens[${idx}][codfabnumero]`;
            if (q)   q.name   = `itens[${idx}][quantidade]`;
            if (pu)  pu.name  = `itens[${idx}][preco_unitario]`;
            if (pt)  pt.name  = `itens[${idx}][preco_total]`;
            if (pUh) pUh.name = `itens[${idx}][pontuacao]`;
            if (pTh) pTh.name = `itens[${idx}][pontuacao_total]`;
        });
    }

    function atualizarContadorItens(){
        const qtdLinhas = tbody.querySelectorAll('tr.linha').length;
        if (contadorItens) contadorItens.textContent = String(qtdLinhas);
    }

    function recalcularLinha(tr){
        const q  = getQtdInt(tr);
        const pu = toN(tr.querySelector('.preco-unit')?.value);
        const pt = tr.querySelector('.preco-total');
        if (pt) pt.value = (q * pu).toFixed(2);

        // pontos totais do item (qtd * pontos unit)
        const pUni = parseInt(tr.querySelector('.pontos-unit')?.value || '0', 10);
        const hTot = tr.querySelector('.pontos-total-hidden');
        if (hTot) hTot.value = (q * pUni);
    }

    function recalcularTotais(){
        let bruto = 0;
        let pontosTot = 0;
        let pontosUnitSomatorio = 0;

        tbody.querySelectorAll('tr.linha').forEach(tr => {
            bruto += toN(tr.querySelector('.preco-total')?.value);
            const q     = getQtdInt(tr);
            const pUnit = parseInt(tr.querySelector('.pontos-unit')?.value || '0', 10);
            pontosTot  += (q * pUnit);
            pontosUnitSomatorio += pUnit;
        });

        if (totalBruto) totalBruto.value = bruto.toFixed(2);
        const desc = toN(totalDesc?.value ?? 0);
        if (totalLiq) totalLiq.value = Math.max(0, bruto - desc).toFixed(2);

        if (totalPontos) totalPontos.textContent = String(pontosTot);

        document.getElementById('pedidoPontuacao')?.setAttribute('value', pontosUnitSomatorio);
        document.getElementById('pedidoPontuacaoTotal')?.setAttribute('value', pontosTot);
    }

    // Disponibiliza global para o Select2 chamar
    window.buscarPrecoEPontos = async function(selectEl){
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt) return;

        const codfab = opt.getAttribute('data-codfab');
        const tr     = selectEl.closest('tr.linha');
        if (!codfab || !tr) return;

        try {
            const r = await fetch(`/produto/bycod/${encodeURIComponent(codfab)}`);
            const d = await r.json();

            const pid = tr.querySelector('.produto-id-hidden');
            if (pid && d?.id) pid.value = d.id;

            const cod = tr.querySelector('.codfab-hidden');
            if (cod) cod.value = codfab;

            const pu = tr.querySelector('.preco-unit');
            if (pu) pu.value = Number(d?.preco_venda ?? 0).toFixed(2);

            const pUni = tr.querySelector('.pontos-unit');
            if (pUni) pUni.value = Number(d?.pontuacao ?? 0);

            // hidden por item atualizados
            const pUh = tr.querySelector('.pontos-unit-hidden');
            if (pUh) pUh.value = parseInt(pUni.value || '0', 10);

            const q   = getQtdInt(tr);
            const pTh = tr.querySelector('.pontos-total-hidden');
            if (pTh) pTh.value = (q * parseInt(pUni.value || '0', 10));

            recalcularLinha(tr);
            recalcularTotais();
        } catch (e) {
            console.error('Erro ao buscar preço/pontos:', e);
        }
    }

    async function carregarPlanos(formaId){
        planoPagamento.innerHTML = `<option value="">Carregando...</option>`;
        if (!formaId){
            planoPagamento.innerHTML = `<option value="">Selecione a forma primeiro...</option>`;
            codplanoHidden?.setAttribute('value','');
            return;
        }
        try{
            const url = URL_PLANOS_BASE.replace('__FORMA__', encodeURIComponent(formaId));
            const r = await fetch(url);
            const data = await r.json();
            let html = `<option value="">Selecione...</option>`;
            (data || []).forEach(p => {
                const nome   = p.descricao || (`Plano #${p.id}`);
                const codigo = p.codigo || ''; // se sua API não retorna, ficará vazio
                html += `<option value="${p.id}" data-codigo="${codigo}">${nome}</option>`;
            });
            planoPagamento.innerHTML = html;
            codplanoHidden?.setAttribute('value','');
        }catch(e){
            console.error('Erro ao carregar planos:', e);
            planoPagamento.innerHTML = `<option value="">Erro ao carregar</option>`;
            codplanoHidden?.setAttribute('value','');
        }
    }

    document.addEventListener('change', function(e){
        const el = e.target;

        if (el.classList.contains('produtoSelect')){
            window.buscarPrecoEPontos(el);
        }

        if (el.classList.contains('quantidade') || el.classList.contains('preco-unit')){
            const tr = el.closest('tr.linha');
            if (tr){
                recalcularLinha(tr);
                recalcularTotais();
            }
        }

        if (el.id === 'totalDesc'){
            recalcularTotais();
        }

        if (el === formaPagamento){
            carregarPlanos(el.value);
        }

        if (el === planoPagamento){
            const opt = el.options[el.selectedIndex];
            const codigo = opt?.getAttribute('data-codigo') || '';
            codplanoHidden?.setAttribute('value', codigo);
        }
    });

    document.addEventListener('input', function(e){
        const el = e.target;
        if (el.classList.contains('quantidade')) {
            el.value = el.value.replace(/[^\d]/g, '');
            if (el.value === '' || parseInt(el.value, 10) < 1) el.value = '1';
            const tr = el.closest('tr.linha');
            if (tr){ recalcularLinha(tr); recalcularTotais(); }
        }
        if (el.classList.contains('preco-unit')) {
            const tr = el.closest('tr.linha');
            if (tr){ recalcularLinha(tr); recalcularTotais(); }
        }
        if (el.id === 'totalDesc') {
            recalcularTotais();
        }
    });

    document.addEventListener('click', function(e){
        if (e.target.closest('.btnDel')){
            const linha = e.target.closest('tr.linha');
            if (!linha) return;
            const total = tbody.querySelectorAll('tr.linha').length;
            if (total > 1){
                linha.remove();
                renomear();
                atualizarContadorItens();
                recalcularTotais();
            }
        }
    });

    btnAdd?.addEventListener('click', () => {
        const node = tpl.content.cloneNode(true);
        tbody.appendChild(node);
        renomear();
        atualizarContadorItens();
        recalcularTotais();

        // inicializa Select2 na nova linha (feito abaixo no init do jQuery com timeout)
    });

    window.addEventListener('load', () => {
        renomear();
        atualizarContadorItens();
        recalcularTotais();
    });
})();
</script>

{{-- jQuery + Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.select2-container .select2-selection--single { height: 38px; }
.select2-container .select2-selection__rendered { line-height: 36px; }
.select2-container .select2-selection__arrow { height: 36px; }
  /* Select2 sempre a 100% da célula */
  select.produtoSelect { width: 100%; }
  .select2-container { width: 100% !important; }
</style>

<style>
/* Aumenta contraste/visibilidade em geral */
button, .btn, [type="submit"] {
  -webkit-font-smoothing: antialiased;
}
</style>

<script>
function initProdutoSelect2($scope){
  $scope.find('select.produtoSelect').select2({
    width: '100%',
    placeholder: 'Selecione...',
    matcher: function(params, data) {
      if ($.trim(params.term) === '') return data;
      if (typeof data.text === 'undefined') return null;

      const term = params.term.toLowerCase();
      const el   = data.element;
      const cod  = (el?.dataset?.codfab || '').toLowerCase();
      const nome = (el?.dataset?.nome   || '').toLowerCase();

      if (data.text.toLowerCase().includes(term)) return data;
      if (cod.includes(term))  return data;
      if (nome.includes(term)) return data;
      return null;
    },
    templateResult: function (data) {
      if (!data.id) return data.text;
      const el   = data.element;
      const cod  = el.dataset.codfab || '';
      const nome = el.dataset.nome   || '';
      return $(`<div>${cod} - ${nome}</div>`);
    },
    templateSelection: function (data) {
      if (!data.id) return data.text;
      const el   = data.element;
      const cod  = el.dataset.codfab || '';
      const nome = el.dataset.nome   || '';
      return `${cod} - ${nome}`;
    }
  })
  .on('select2:select', function () {
    if (typeof window.buscarPrecoEPontos === 'function') {
      window.buscarPrecoEPontos(this);
    } else {
      this.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
}

$(function(){
  initProdutoSelect2($(document));

  const btnAdd = document.getElementById('btnAdd');
  const tpl    = document.getElementById('tplLinha');
  const tbody  = document.getElementById('linhas');

  if (btnAdd && tpl && tbody){
    btnAdd.addEventListener('click', () => {
      setTimeout(() => {
        const $last = $(tbody.querySelector('tr.linha:last-child'));
        initProdutoSelect2($last);
      }, 0);
    });
  }

  $('.produtoSelect').each(function(){
    if (this.value && typeof window.buscarPrecoEPontos === 'function') {
      window.buscarPrecoEPontos(this);
    }
  });
});
</script>
@endsection
