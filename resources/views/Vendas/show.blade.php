{{-- resources/views/vendas/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-700">
                Pedido de Venda • #{{ $pedido->id }}
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('vendas.index') }}"
                   class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                    Voltar
                </a>

                @if (Route::has('vendas.edit'))
                    <a href="{{ route('vendas.edit', $pedido->id) }}"
                       class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Editar
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $fmtPerc = fn($v) => number_format((float)$v, 2, ',', '.') . '%';
    @endphp

    <div class="max-w-7xl mx-auto p-4 space-y-4">

        {{-- RESUMO (pedido) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Status</div>
                <div class="mt-1 inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                    {{ $pedido->status }}
                </div>
                @if(!empty($pedido->canceled_at))
                    <div class="text-[11px] text-red-600 mt-2">
                        Cancelado em: {{ \Carbon\Carbon::parse($pedido->canceled_at)->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Data do pedido</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $pedido->data_pedido?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Previsão entrega</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $pedido->previsao_entrega?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Valor líquido (cabeçalho)</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $fmt($pedido->valor_liquido ?? 0) }}
                </div>
                <div class="text-[11px] text-gray-500 mt-1">
                    Total: {{ $fmt($pedido->valor_total ?? 0) }} • Desc: {{ $fmt($pedido->valor_desconto ?? 0) }}
                </div>
            </div>
        </div>

        {{-- RENTABILIDADE (itens) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Receita líquida (itens)</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $fmt($totais['receita_liquida_itens'] ?? 0) }}
                </div>
                <div class="text-[11px] text-gray-500 mt-1">
                    (Σ (total - desconto) dos itens)
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Custo (preço compra)</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $fmt($totais['custo_total'] ?? 0) }}
                </div>
                <div class="text-[11px] text-gray-500 mt-1">
                    (Σ qtd × produto.preco_compra)
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Lucro</div>
                <div class="text-lg font-semibold {{ (($totais['lucro_total'] ?? 0) < 0) ? 'text-red-600' : 'text-green-700' }}">
                    {{ $fmt($totais['lucro_total'] ?? 0) }}
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-xs text-gray-500">Margem</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $fmtPerc($totais['margem_total'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- CLIENTE / PAGAMENTO / PONTOS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-700 mb-2">Cliente</div>

                <div class="text-gray-800 font-semibold">
                    {{ $pedido->cliente->nome ?? '-' }}
                </div>

                <div class="text-sm text-gray-600 mt-1">
                    WhatsApp: {{ $pedido->cliente->whatsapp ?? $pedido->cliente->telefone ?? '-' }}
                </div>

                @if(!empty($pedido->cliente?->email))
                    <div class="text-sm text-gray-600 mt-1">
                        Email: {{ $pedido->cliente->email }}
                    </div>
                @endif

                @if(!empty($pedido->indicador))
                    <div class="text-sm text-gray-600 mt-2">
                        Indicador: <span class="font-semibold">{{ $pedido->indicador->nome }}</span>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-700 mb-2">Pagamento</div>

                <div class="text-sm text-gray-700">
                    Forma: <span class="font-semibold">{{ $pedido->forma->nome ?? '-' }}</span>
                </div>

                <div class="text-sm text-gray-700 mt-1">
                    Plano: <span class="font-semibold">{{ $pedido->plano->descricao ?? '-' }}</span>
                </div>

                <div class="text-sm text-gray-700 mt-1">
                    Enviar msg cliente:
                    <span class="font-semibold">{{ ($pedido->enviar_msg_cliente ?? 0) ? 'SIM' : 'NÃO' }}</span>
                </div>

                @if(!empty($pedido->observacao))
                    <div class="text-xs text-gray-500 mt-2">
                        Obs: {{ \Illuminate\Support\Str::limit($pedido->observacao, 120) }}
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-700 mb-2">Pontos</div>
                <div class="text-sm text-gray-700">
                    Pontos (itens): <span class="font-semibold">{{ (int)($totais['pontos'] ?? 0) }}</span>
                </div>
                <div class="text-sm text-gray-700 mt-1">
                    Pontos total: <span class="font-semibold">{{ (int)($totais['pontos_total'] ?? 0) }}</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-2">
                    Base: appitemvenda.pontuacao e pontuacao_total
                </div>
            </div>
        </div>

        {{-- ITENS (com rentabilidade por item + última compra) --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <div class="p-4 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-gray-700">Itens</div>
                    <div class="text-xs text-gray-500">
                        Qtd total: {{ (int)($totais['qtd_itens'] ?? 0) }}
                        • Linhas: {{ (int)($pedido->itens?->count() ?? 0) }}
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-xs text-gray-500">Total itens / Desconto itens</div>
                    <div class="font-semibold text-gray-800">
                        {{ $fmt($totais['total_itens'] ?? 0) }}
                        <span class="text-gray-400 font-normal">/</span>
                        {{ $fmt($totais['desc_itens'] ?? 0) }}
                    </div>
                </div>
            </div>

            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Produto</th>
                        <th class="text-left px-4 py-3">Cod</th>
                        <th class="text-right px-4 py-3">Qtd</th>
                        <th class="text-right px-4 py-3">Venda Unit</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-right px-4 py-3">Desc</th>
                        <th class="text-right px-4 py-3">Líquido</th>

                        <th class="text-right px-4 py-3">Compra Unit</th>
                        <th class="text-right px-4 py-3">Últ compra</th>
                        <th class="text-right px-4 py-3">Qtd últ compra</th>

                        <th class="text-right px-4 py-3">Custo</th>
                        <th class="text-right px-4 py-3">Lucro</th>
                        <th class="text-right px-4 py-3">Margem</th>
                        <th class="text-right px-4 py-3">Pts</th>
                        <th class="text-right px-4 py-3">Pts Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($pedido->itens as $it)
                        @php
                            $qtd = (float)($it->quantidade ?? 0);
                            $total = (float)($it->preco_total ?? 0);
                            $desc  = (float)($it->valor_desconto ?? 0);
                            $liq   = $total - $desc;

                            $compraUnit = (float)($it->produto->preco_compra ?? 0);
                            $custo = $qtd * $compraUnit;

                            $lucro = $liq - $custo;
                            $margem = $liq > 0 ? ($lucro / $liq) * 100 : 0;

                            $pts = (int)($it->pontuacao ?? 0);
                            $ptsTotal = (int)($it->pontuacao_total ?? 0);

                            $semCusto = $compraUnit <= 0.000001;

                            $ultData = $it->ultima_compra_data ?? null;
                            $ultQtd  = $it->ultima_compra_qtd ?? null;
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-800">
                                <div class="font-semibold">
                                    {{ $it->produto->nome ?? ('Produto #' . $it->produto_id) }}
                                </div>
                                @if($semCusto)
                                    <div class="text-[11px] text-orange-600 mt-1">
                                        ⚠ Sem preço de compra cadastrado (preco_compra = 0)
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600">{{ $it->codfabnumero ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ (int)$qtd }}</td>

                            <td class="px-4 py-3 text-right">{{ $fmt($it->preco_unitario ?? 0) }}</td>
                            <td class="px-4 py-3 text-right">{{ $fmt($total) }}</td>
                            <td class="px-4 py-3 text-right">{{ $fmt($desc) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ $fmt($liq) }}</td>

                            <td class="px-4 py-3 text-right">{{ $fmt($compraUnit) }}</td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                @if(!empty($ultData))
                                    {{ \Carbon\Carbon::parse($ultData)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                {{ !is_null($ultQtd) ? (int)$ultQtd : '-' }}
                            </td>

                            <td class="px-4 py-3 text-right">{{ $fmt($custo) }}</td>

                            <td class="px-4 py-3 text-right font-semibold {{ $lucro < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ $fmt($lucro) }}
                            </td>

                            <td class="px-4 py-3 text-right">{{ $fmtPerc($margem) }}</td>

                            <td class="px-4 py-3 text-right">{{ $pts }}</td>
                            <td class="px-4 py-3 text-right">{{ $ptsTotal }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="px-4 py-8 text-center text-gray-500">
                                Nenhum item encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- CONTAS A RECEBER --}}
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-sm font-semibold text-gray-700 mb-2">Contas a Receber</div>

            @if(($pedido->contasReceber?->count() ?? 0) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-4 py-2">Parcela</th>
                                <th class="text-left px-4 py-2">Vencimento</th>
                                <th class="text-right px-4 py-2">Valor</th>
                                <th class="text-left px-4 py-2">Status</th>
                                <th class="text-left px-4 py-2">Pagamento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($pedido->contasReceber as $cr)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        {{ $cr->parcela ?? '-' }}/{{ $cr->total_parcelas ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if(!empty($cr->data_vencimento))
                                            {{ \Carbon\Carbon::parse($cr->data_vencimento)->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{ $fmt($cr->valor ?? 0) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                            {{ $cr->status ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">
                                        @if(!empty($cr->data_pagamento))
                                            {{ \Carbon\Carbon::parse($cr->data_pagamento)->format('d/m/Y') }}
                                            <span class="text-gray-400">•</span>
                                            {{ $fmt($cr->valor_pago ?? 0) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-sm text-gray-500">Nenhuma parcela gerada para este pedido.</div>
            @endif
        </div>

        {{-- OBSERVAÇÕES (completo) --}}
        @if(!empty($pedido->observacao))
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-700 mb-2">Observações</div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $pedido->observacao }}</div>
            </div>
        @endif

        <div class="text-[11px] text-gray-500">
            Rentabilidade usa <strong>produto.preco_compra</strong>. Última compra vem de <strong>appcompraproduto + appcompra</strong>.
        </div>

    </div>
</x-app-layout>
