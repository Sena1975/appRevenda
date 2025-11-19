{{-- resources/views/financeiro/recibo.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Recibo {{ $recibo_numero ?? '---' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        * {
            box-sizing: border-box;
        }

        .actions {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background: #f8f9fa;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }

        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .btn:hover {
            background: #e5e7eb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .no-print {
            /* aparece na tela, some na impressão */
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .page {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        .h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted {
            color: #666;
            font-size: 12px;
        }

        .box {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 8px;
        }

        .col {
            flex: 1;
            min-width: 160px;
        }

        .right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            background: #f3f4f6;
            font-size: 11px;
        }

        .total {
            font-size: 18px;
            font-weight: 700;
        }

        .mt2 {
            margin-top: 8px;
        }

        .mt3 {
            margin-top: 12px;
        }

        .mt4 {
            margin-top: 16px;
        }

        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 10px 0 12px;
        }

        table.itens {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        table.itens th,
        table.itens td {
            border: 1px solid #ddd;
            padding: 4px 6px;
        }

        table.itens th {
            background: #f8f9fa;
            text-align: left;
        }

        table.itens td.num {
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="no-print actions">
            <button type="button" class="btn" onclick="window.history.back();">
                Voltar
            </button>

            <button type="button" class="btn btn-primary" onclick="window.print();">
                Imprimir
            </button>
        </div>

        @php
            // $c vem do controller; apenas normalizamos alguns campos
            $valorTitulo = (float) ($c->valor ?? 0);
            $valorPago = $c->valor_pago ?? null;
        @endphp

        {{-- Cabeçalho --}}
        <div class="row" style="align-items:center; margin-bottom: 12px;">
            <div class="col">
                <div class="h1">Recibo</div>
                <div class="muted">Nº {{ $recibo_numero ?? '---' }}</div>
            </div>
            <div class="col right">
                <div class="badge">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Dados principais --}}
        <div class="box">
            <div class="row">
                <div class="col">
                    <strong>Cliente:</strong><br>
                    {{ $c->cliente_nome ?? '-' }}
                </div>
                <div class="col">
                    <strong>Revendedora:</strong><br>
                    {{ $c->revendedora_nome ?? '-' }}
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <strong>Parcela:</strong><br>
                    {{ $c->parcela ?? '-' }}/{{ $c->total_parcelas ?? '-' }}
                </div>
                <div class="col">
                    <strong>Vencimento:</strong><br>
                    @if (!empty($c->data_vencimento))
                        {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
                    @else
                        &ndash;
                    @endif
                </div>
                <div class="col">
                    <strong>Pagamento:</strong><br>
                    @if (!empty($c->data_pagamento))
                        {{ \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') }}
                    @else
                        &ndash;
                    @endif
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col">
                    <strong>Status:</strong><br>
                    {{ $statusUpper === 'PAGO' ? 'PAGO' : $statusUpper }}
                </div>
                <div class="col right">
                    <div class="muted">Valor da parcela</div>
                    <div class="total">
                        R$ {{ number_format($valorTitulo, 2, ',', '.') }}
                    </div>

                    @if (!is_null($valorPago))
                        <div class="muted mt2">Valor pago</div>
                        <div class="total">
                            R$ {{ number_format((float) $valorPago, 2, ',', '.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Observações --}}
            @if (!empty($c->observacao))
                <div class="mt3">
                    <strong>Observações:</strong>
                    <div class="muted" style="white-space:pre-wrap;">
                        {{ $c->observacao }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Itens do pedido --}}
        @if (isset($itens) && $itens->count())
            <div class="box mt4">
                <strong>Produtos do pedido #{{ $c->pedido_id }}</strong>

                <table class="itens">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Cód. Fab.</th>
                            <th>Descrição</th>
                            <th style="width: 60px; text-align:right;">Qtd</th>
                            <th style="width: 90px; text-align:right;">Vlr Unit. (R$)</th>
                            <th style="width: 90px; text-align:right;">Vlr Total (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalItens = 0;
                        @endphp
                        @foreach ($itens as $item)
                            @php $totalItens += (float) $item->preco_total; @endphp
                            <tr>
                                <td>{{ $item->codfabnumero }}</td>
                                <td>{{ $item->produto_nome }}</td>
                                <td class="num">{{ (int) $item->quantidade }}</td>
                                <td class="num">{{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                                <td class="num">{{ number_format((float) $item->preco_total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="num"><strong>Total dos produtos</strong></td>
                            <td class="num"><strong>{{ number_format($totalItens, 2, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <div class="muted mt4">
            * Documento gerado pelo sistema em {{ now()->format('d/m/Y H:i') }}.
        </div>
    </div>
</body>

</html>
