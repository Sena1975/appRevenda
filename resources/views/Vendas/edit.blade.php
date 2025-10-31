{{-- resources/views/vendas/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Editar Pedido #{{ $pedido->id }}</h1>

    @if($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('vendas.update', $pedido->id) }}" method="POST" id="formVenda">
        @csrf
        @method('PUT')

        {{-- DADOS PRINCIPAIS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium mb-1">Cliente *</label>
                <select name="cliente_id" class="w-full border rounded" required>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" @selected($pedido->cliente_id == $c->id)>{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Revendedora</label>
                <select name="revendedora_id" class="w-full border rounded">
                    <option value="">(Opcional)</option>
                    @foreach($revendedoras as $r)
                        <option value="{{ $r->id }}" @selected($pedido->revendedora_id == $r->id)>{{ $r->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Forma de Pagamento *</label>
                <select name="forma_pagamento_id" id="formaPagamento" class="w-full border rounded" required>
                    @foreach($formas as $f)
                        <option value="{{ $f->id }}" @selected($pedido->forma_pagamento_id == $f->id)>
                            {{ $f->nome ?? $f->descricao ?? ('Forma #'.$f->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Plano de Pagamento *</label>
                <select name="plano_pagamento_id" id="planoPagamento" class="w-full border rounded" required>
                    @foreach($planos as $p)
                        <option value="{{ $p->id }}" @selected($pedido->plano_pagamento_id == $p->id)>
                            {{ $p->descricao ?? ('Plano #'.$p->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Data do Pedido</label>
                <input type="date" name="data_pedido" class="w-full border rounded"
                       value="{{ \Carbon\Carbon::parse($pedido->data_pedido)->format('Y-m-d') }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Previsão de Entrega</label>
                <input type="date" name="previsao_entrega" class="w-full border rounded"
                       value="{{ $pedido->previsao_entrega ? \Carbon\Carbon::parse($pedido->previsao_entrega)->format('Y-m-d') : '' }}">
            </div>
        </div>

        {{-- RESUMO RÁPIDO --}}
        <div class="flex flex-wrap gap-4 items-center mb-2 text-sm">
            <span>Itens: <strong id="contadorItens">{{ $pedido->itens->count() }}</strong></span>
            <span>Total de Pontos: <strong id="totalPontos">0</strong></span>
        </div>

        {{-- ITENS --}}
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Itens do Pedido</h2>
            <button type="button" id="btnAdd" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Adicionar item</button>
        </div>

        <div class="overflow-x-auto mb-4">
            <table class="min-w-full border" id="tblItens">
                <thead class="bg-gray-50 text-sm">
                    <tr>
                        <th class="px-2 py-2 text-left w-[360px]">Produto (CODFAB - Nome)</th>
                        <th class="px-2 py-2 text-right w-24">Qtd</th>
                        <th class="px-2 py-2 text-right w-24">Pontos</th>
                        <th class="px-2 py-2 text-right w-32">R$ Unit</th>
                        <th class="px-2 py-2 text-right w-32">R$ Total</th>
                        <th class="px-2 py-2 text-center w-16">Ação</th>
                    </tr>
                </thead>
                <tbody id="linhas">
                    @foreach($pedido->itens as $idx => $it)
                        <tr class="linha border-t">
                            <td class="px-2 py-2">
                                <input type="hidden" name="itens[{{ $idx }}][produto_id]" class="produto-id-hidden" value="{{ $it->produto_id }}">
                                <input type="hidden" name="itens[{{ $idx }}][codfabnumero]" class="codfab-hidden" value="{{ $it->codfabnumero }}">
                                <select class="produtoSelect w-full border rounded" required>
                                    @foreach($produtos as $p)
                                        <option value="{{ $p->id }}"
                                            data-codfab="{{ $p->codfabnumero }}"
                                            data-nome="{{ $p->nome }}"
                                            @selected($p->id == $it->produto_id)
                                        >
                                            {{ $p->codfabnumero }} - {{ $p->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" min="1" step="1" value="{{ (int)$it->quantidade }}"
                                       name="itens[{{ $idx }}][quantidade]"
                                       class="quantidade w-full border rounded text-right" inputmode="numeric" pattern="\d*">
                            </td>
                            <td class="px-2 py-2">
                                {{-- pontos unit (só exibição) --}}
                                <input type="number" min="0" step="1" class="pontos-unit w-full border rounded text-right" readonly>
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" min="0" step="0.01" value="{{ number_format($it->preco_unitario, 2, '.', '') }}"
                                       name="itens[{{ $idx }}][preco_unitario]"
                                       class="preco-unit w-full border rounded text-right">
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" min="0" step="0.01" value="{{ number_format($it->preco_total, 2, '.', '') }}"
                                       name="itens[{{ $idx }}][preco_total]"
                                       class="preco-total w-full border rounded text-right" readonly>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <button type="button" class="btnDel px-2 py-1 bg-red-500 text-white rounded text-xs">X</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="4" class="px-2 py-2 text-right font-semibold">Total Bruto (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_total" id="totalBruto" class="w-full border rounded text-right" readonly
                                   value="{{ number_format($pedido->valor_total, 2, '.', '') }}">
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-2 py-2 text-right">Desconto (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_desconto" id="totalDesc" class="w-full border rounded text-right"
                                   value="{{ number_format($pedido->valor_desconto, 2, '.', '') }}">
                        </td>
                        <td></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="4" class="px-2 py-2 text-right font-semibold">Total Líquido (R$):</td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" name="valor_liquido" id="totalLiq" class="w-full border rounded text-right" readonly
                                   value="{{ number_format($pedido->valor_liquido, 2, '.', '') }}">
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- OBSERVAÇÃO --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Observação</label>
            <textarea name="observacao" rows="3" class="w-full border rounded">{{ $pedido->observacao }}</textarea>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('vendas.index') }}" class="px-3 py-2 border rounded">Cancelar</a>
            <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded">Salvar alterações</button>
        </div>
    </form>
</div>

{{-- IMPORTANTE: incluir os MESMOS scripts/js do create (Select2, buscarPrecoEPontos, carregarPlanos, toN, getQtdInt, etc.) --}}
{{-- Para economizar, você pode reutilizar exatamente o bloco de <script> do create e só ajustar a URL base dos planos: --}}
<script>
    const URL_PLANOS_BASE = @json(route('planopagamento.getByForma', ['forma_id' => '__FORMA__']));
</script>
{{-- Cole aqui o MESMO JS do create.blade (as funções: toN, getQtdInt, renomear, atualizarContadorItens, recalcularLinha, recalcularTotais, buscarPrecoEPontos, carregarPlanos, listeners, e Select2 init). --}}
{{-- Dica: se quiser evitar duplicação, extraia esse JS para um arquivo .js e inclua nas duas telas. --}}
@endsection
