{{-- resources/views/Compras/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Novo Pedido de Compra</h1>

    @if(session('error'))
      <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
    @endif
    @if(session('success'))
      <div class="mb-3 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="mb-3 p-3 bg-yellow-100 text-yellow-900 rounded">
        @foreach($errors->all() as $e)
          <div>{{ $e }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('compras.store') }}">
        @csrf

        {{-- DADOS DO PEDIDO (ajuste conforme seu modelo) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium">Fornecedor *</label>
                <select name="fornecedor_id" class="w-full border rounded h-10" required>
                    <option value="">Selecione...</option>
                    @foreach($fornecedores ?? [] as $f)
                        <option value="{{ $f->id }}">{{ $f->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium">Data do Pedido *</label>
                <input type="date" name="data_pedido" value="{{ date('Y-m-d') }}" class="w-full border rounded h-10 px-2" required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium">Observação</label>
                <input type="text" name="observacao" class="w-full border rounded h-10 px-2">
            </div>
        </div>

        <div class="flex flex-wrap gap-4 items-center mb-2 text-sm">
            <span>Itens: <strong id="contadorItens">1</strong></span>
            <span>Total de Pontos: <strong id="totalPontos">0</strong></span>
        </div>

        {{-- ITENS DO PEDIDO --}}
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Itens do Pedido</h2>
            <button type="button" id="btnAdd" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Adicionar item</button>
        </div>

        <div class="overflow-x-auto mb-4">
            <table class="min-w-full border table-fixed" id="tblItens">
                <colgroup>
                    <col style="width: 45%">  {{-- Produto --}}
                    <col style="width: 8%">   {{-- Qtd --}}
                    <col style="width: 8%">   {{-- Estoque --}}
                    <col style="width: 8%">   {{-- Pontos --}}
                    <col style="width: 10%">  {{-- R$ Unit (compra) --}}
                    <col style="width: 10%">  {{-- R$ Total --}}
                    <col style="width: 6rem"> {{-- Ação --}}
                </colgroup>
                <thead class="bg-gray-50 text-sm">
                    <tr>
                        <th class="px-2 py-2 text-left">Produto (CODFAB - Nome)</th>
                        <th class="px-2 py-2 text-right">Qtd</th>
                        <th class="px-2 py-2 text-right">Estoque</th>
                        <th class="px-2 py-2 text-right">Pontos</th>
                        <th class="px-2 py-2 text-right">R$ Unit</th>
                        <th class="px-2 py-2 text-right">R$ Total</th>
                        <th class="px-2 py-2 text-center">Ação</th>
                    </tr>
                </thead>
                <tbody id="linhas">
                    {{-- Linha inicial (índice 0) --}}
                    <tr class="linha border-t">
                        <td class="px-2 py-2">
                            <input type="hidden" name="itens[0][produto_id]" class="produto-id-hidden">
                            <input type="hidden" name="itens[0][codfabnumero]" class="codfab-hidden">
                            <input type="hidden" name="itens[0][pontuacao]" class="pontos-unit-hidden">
                            <input type="hidden" name="itens[0][pontuacao_total]" class="pontos-total-hidden">

                            <select class="produtoSelect w-full border rounded" required>
                                <option value="">Selecione...</option>
                                @foreach($produtos ?? [] as $p)
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

                        {{-- NOVO: estoque visível + hidden --}}
                        <td class="px-2 py-2">
                            <input type="text" class="estoque-atual w-full border rounded text-right bg-gray-50" readonly>
                            <input type="hidden" name="itens[0][estoque_atual]" class="estoque-hidden">
                        </td>

                        <td class="px-2 py-2">
                            <input type="number" min="0" step="1" class="pontos-unit w-full border rounded text-right" readonly>
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="0" step="0.01" name="itens[0][preco_unitario]" class="preco-unit w-full border rounded text-right">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="0" step="0.01" class="preco-total w-full border rounded text-right" readonly>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <button type="button" class="btnDel px-2 py-1 bg-red-50 text-red-600 border rounded">Excluir</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- TOTAIS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Observação do Pedido</label>
                <textarea name="observacao_pedido" class="w-full border rounded p-2" rows="5"></textarea>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span>Total Bruto (R$):</span>
                    <input type="number" step="0.01" id="totalBruto" name="total_bruto" class="w-40 border rounded text-right bg-gray-50" readonly>
                </div>
                <div class="flex items-center justify-between">
                    <span>Desconto (R$):</span>
                    <input type="number" step="0.01" id="totalDesc" name="desconto" class="w-40 border rounded text-right" value="0">
                </div>
                <div class="flex items-center justify-between">
                    <span>Total Líquido (R$):</span>
                    <input type="number" step="0.01" id="totalLiq" name="total_liquido" class="w-40 border rounded text-right bg-gray-50" readonly>
                </div>

                {{-- HIDDENs do pedido --}}
                <input type="hidden" name="pontuacao" id="pedidoPontuacao">
                <input type="hidden" name="pontuacao_total" id="pedidoPontuacaoTotal">
            </div>
        </div>

        <div class="mt-6 flex gap-3 justify-end">
            <a href="{{ url()->previous() }}" class="px-4 py-2 border rounded">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Salvar</button>
        </div>
    </form>
</div>

{{-- TEMPLATE DA LINHA PARA CLONAGEM --}}
<template id="tplLinha">
    <tr class="linha border-t">
        <td class="px-2 py-2">
            <input type="hidden" name="__idx__[produto_id]" class="produto-id-hidden">
            <input type="hidden" name="__idx__[codfabnumero]" class="codfab-hidden">
            <input type="hidden" name="__idx__[pontuacao]" class="pontos-unit-hidden">
            <input type="hidden" name="__idx__[pontuacao_total]" class="pontos-total-hidden">

            <select class="produtoSelect w-full border rounded" required>
                <option value="">Selecione...</option>
                @foreach($produtos ?? [] as $p)
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
            <input type="number" min="1" step="1" value="1" name="__idx__[quantidade]"
                   class="quantidade w-full border rounded text-right" inputmode="numeric" pattern="\d*">
        </td>

        {{-- NOVO: estoque visível + hidden --}}
        <td class="px-2 py-2">
            <input type="text" class="estoque-atual w-full border rounded text-right bg-gray-50" readonly>
            <input type="hidden" name="__idx__[estoque_atual]" class="estoque-hidden">
        </td>

        <td class="px-2 py-2">
            <input type="number" min="0" step="1" class="pontos-unit w-full border rounded text-right" readonly>
        </td>
        <td class="px-2 py-2">
            <input type="number" min="0" step="0.01" name="__idx__[preco_unitario]" class="preco-unit w-full border rounded text-right">
        </td>
        <td class="px-2 py-2">
            <input type="number" min="0" step="0.01" class="preco-total w-full border rounded text-right" readonly>
        </td>
        <td class="px-2 py-2 text-center">
            <button type="button" class="btnDel px-2 py-1 bg-red-50 text-red-600 border rounded">Excluir</button>
        </td>
    </tr>
</template>

{{-- jQuery + Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.select2-container .select2-selection--single { height: 38px; }
.select2-container .select2-selection__rendered { line-height: 36px; }
.select2-container .select2-selection__arrow { height: 36px; }
</style>

<script>
(function(){
  // ---- util ----
  function parseMoney(n){ return Number(n || 0); }
  function formatMoney(n){ return (Number(n||0)).toFixed(2); }

  function getQtdInt(tr){
    const i = tr.querySelector('input[name*="[quantidade]"]');
    return parseInt(i?.value || '0', 10) || 0;
  }

  // ---- totais ----
  function recalcularLinha(tr){
    const qtd   = getQtdInt(tr);
    const preco = parseMoney(tr.querySelector('.preco-unit')?.value);
    const total = qtd * preco;
    const totalInput = tr.querySelector('.preco-total');
    if (totalInput) totalInput.value = formatMoney(total);
  }

  function somarColuna(selector){
    let soma = 0;
    document.querySelectorAll(selector).forEach(el => {
      soma += parseMoney(el.value);
    });
    return soma;
  }

  function atualizarTotais(){
    const bruto = somarColuna('.preco-total');
    const desc  = parseMoney(document.getElementById('totalDesc')?.value);
    const liq   = bruto - desc;
    const tp    = Array.from(document.querySelectorAll('.pontos-total-hidden'))
                    .reduce((acc, el)=> acc + parseInt(el.value||'0',10), 0);
    const pu    = Array.from(document.querySelectorAll('.pontos-unit-hidden'))
                    .reduce((acc, el)=> acc + parseInt(el.value||'0',10), 0);

    document.getElementById('totalBruto')?.setAttribute('value', formatMoney(bruto));
    document.getElementById('totalLiq')?.setAttribute('value', formatMoney(liq));
    document.getElementById('totalPontos').textContent = String(tp);
    document.getElementById('pedidoPontuacao')?.setAttribute('value', pu);
    document.getElementById('pedidoPontuacaoTotal')?.setAttribute('value', tp);
  }

  function atualizarContadorItens(){
    const n = document.querySelectorAll('#linhas tr.linha').length;
    document.getElementById('contadorItens').textContent = String(n);
  }

  // ---- renomeia campos quando adiciona/remove ----
  function renomear(){
    const trs = document.querySelectorAll('#linhas tr.linha');
    trs.forEach((tr, idx) => {
      tr.querySelectorAll('input[name], select[name]').forEach(el => {
        el.name = el.name
          .replace(/itens\[\d+\]/g, `itens[${idx}]`)
          .replace(/__idx__/g, `itens[${idx}]`);
      });
    });
    atualizarContadorItens();
  }

  // ---- Select2 por escopo ----
  function initProdutoSelect2($scope){
    $scope.find('select.produtoSelect').select2({
      width: '100%',
      placeholder: 'Selecione...',
      templateResult: function (data) {
        if (!data.id || !data.element) return data.text;
        const el   = data.element;
        const cod  = el.dataset.codfab || '';
        const nome = el.dataset.nome   || '';
        const wrap = document.createElement('div');
        wrap.innerHTML = `
          <div class="text-sm font-medium">${cod} - ${nome}</div>
          <div class="text-xs text-gray-500">Estoque: (preenche ao selecionar)</div>
        `;
        return wrap;
      },
      templateSelection: function (data) {
        if (!data.id || !data.element) return data.text;
        const el   = data.element;
        const cod  = el.dataset.codfab || '';
        const nome = el.dataset.nome   || '';
        return `${cod} - ${nome}`;
      }
    })
    .on('select2:select', function () {
      if (typeof window.buscarPrecoEPontosCompra === 'function') {
        window.buscarPrecoEPontosCompra(this);
      } else {
        this.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  // ---- listeners globais ----
  document.addEventListener('input', function(e){
    const tr = e.target.closest('tr.linha');
    if (!tr) return;

    if (e.target.classList.contains('quantidade') ||
        e.target.classList.contains('preco-unit')) {
      if (e.target.classList.contains('quantidade')) {
        // atualiza pontos totais por linha
        const pUni = parseInt(tr.querySelector('.pontos-unit-hidden')?.value || '0', 10);
        const qtd  = getQtdInt(tr);
        const pTot = tr.querySelector('.pontos-total-hidden');
        if (pTot) pTot.value = (qtd * pUni);
      }

      recalcularLinha(tr);
      atualizarTotais();
    }
  });

  document.getElementById('totalDesc')?.addEventListener('input', atualizarTotais);

  // ---- adicionar/remover linha ----
  const btnAdd = document.getElementById('btnAdd');
  const tpl    = document.getElementById('tplLinha');
  const tbody  = document.getElementById('linhas');

  if (btnAdd && tpl && tbody){
    btnAdd.addEventListener('click', () => {
      const clone = tpl.content.firstElementChild.cloneNode(true);
      tbody.appendChild(clone);
      renomear();

      // ativa select2 na nova linha
      setTimeout(() => {
        const $last = window.jQuery ? jQuery(tbody.querySelector('tr.linha:last-child')) : null;
        if ($last) initProdutoSelect2($last);
      }, 0);
    });

    tbody.addEventListener('click', (e) => {
      if (e.target.closest('.btnDel')) {
        e.target.closest('tr.linha')?.remove();
        renomear();
        atualizarTotais();
      }
    });
  }

  // ---- inicialização ----
  window.addEventListener('load', () => {
    const $doc = window.jQuery ? jQuery(document) : null;
    if ($doc) initProdutoSelect2($doc);
    renomear();
    atualizarTotais();
  });
})();
</script>

<script>
// COMPRA: usa preco_compra
window.buscarPrecoEPontosCompra = async function(selectEl){
  const $sel = window.jQuery ? jQuery(selectEl) : null;

  let opt = selectEl.querySelector('option:checked') ||
            ($sel && $sel.select2 && $sel.select2('data')?.[0]?.element) ||
            null;

  if (!opt) {
    const val = selectEl.value;
    opt = val ? selectEl.querySelector(`option[value="${CSS.escape(val)}"]`) : null;
  }
  if (!opt) return;

  const codfab = opt.getAttribute('data-codfab') || '';
  const nome   = opt.getAttribute('data-nome')   || '';
  const tr     = selectEl.closest('tr.linha');
  if (!tr) return;

  async function consulta(q){
    const url = `/api/produtos/buscar?q=${encodeURIComponent(q)}&limit=1`;
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    try { return await r.json(); } catch { return []; }
  }

  try {
    // tenta por código; se vazio, por nome
    let arr = codfab ? await consulta(codfab) : [];
    if (!Array.isArray(arr) || arr.length === 0) {
      arr = nome ? await consulta(nome) : [];
    }
    if (!Array.isArray(arr) || arr.length === 0) {
      console.warn('Produto não encontrado via endpoint', {codfab, nome});
      return;
    }
    const p = arr[0];

    // campos da linha
    const precoInput = tr.querySelector('.preco-unit');
    const qtdInput   = tr.querySelector('input[name*="[quantidade]"]');

    const pontosVisInput   = tr.querySelector('.pontos-unit');
    const pontosUnitHidden = tr.querySelector('.pontos-unit-hidden');
    const pontosTotalHidden= tr.querySelector('.pontos-total-hidden');
    const codfabHidden     = tr.querySelector('.codfab-hidden');
    const prodIdHidden     = tr.querySelector('.produto-id-hidden');

    // COMPRA => usa preco_compra
    if (precoInput) precoInput.value = (Number(p.preco_compra || 0)).toFixed(2);

    const pontos = parseInt(p.pontos || 0, 10);
    if (pontosVisInput)    pontosVisInput.value = pontos;
    if (pontosUnitHidden)  pontosUnitHidden.value  = pontos;

    const qtd = parseInt((qtdInput && qtdInput.value) ? qtdInput.value : '1', 10) || 1;
    if (pontosTotalHidden) pontosTotalHidden.value = (qtd * pontos);

    if (codfabHidden)      codfabHidden.value      = p.codigo_fabrica || codfab || '';
    if (prodIdHidden && opt.value) prodIdHidden.value = opt.value;

    // ESTOQUE
    const estoqueVal   = parseInt(p.qtd_estoque || 0, 10);
    const estoqueInput = tr.querySelector('.estoque-atual');
    if (estoqueInput) estoqueInput.value = estoqueVal;

    let estoqueHidden = tr.querySelector('.estoque-hidden');
    if (!estoqueHidden) {
      estoqueHidden = document.createElement('input');
      estoqueHidden.type = 'hidden';
      estoqueHidden.className = 'estoque-hidden';
      estoqueHidden.name = (qtdInput?.name || '').replace('[quantidade]','[estoque_atual]') || 'estoque_atual[]';
      tr.appendChild(estoqueHidden);
    }
    estoqueHidden.value = estoqueVal;

    const td = selectEl.closest('td');
    if (td) {
      let badge = td.querySelector('.estoqueAtual-badge');
      if (!badge) {
        badge = document.createElement('div');
        badge.className = 'estoqueAtual-badge text-xs text-gray-600 mt-1';
        td.appendChild(badge);
      }
      badge.textContent = `Estoque atual: ${estoqueVal}`;
    }

    // recálculos finais
    if (precoInput) precoInput.dispatchEvent(new Event('input', { bubbles: true }));
    setTimeout(() => {
      const fnLinha = (typeof recalcularLinha === 'function')  && recalcularLinha;
      const fnTot   = (typeof atualizarTotais === 'function')  && atualizarTotais;
      fnLinha && fnLinha(tr);
      fnTot   && fnTot();
    }, 0);
  } catch (e) {
    console.error('Erro ao buscar preço/pontos/estoque (compra):', e);
  }
}
</script>
@endsection
