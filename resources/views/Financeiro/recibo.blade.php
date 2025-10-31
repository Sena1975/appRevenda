<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
        .titulo { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 10px; }
        .linha { border-top: 1px solid #ccc; margin: 10px 0; }
        .assinatura { margin-top: 60px; text-align: center; }
    </style>
</head>
<body>
    <div class="titulo">RECIBO DE PAGAMENTO</div>

    <p>Recebemos de <strong>{{ $conta->cliente_nome ?? 'Cliente' }}</strong>
    a importância de <strong>R$ {{ number_format($valor, 2, ',', '.') }}</strong>,
    referente ao pedido nº <strong>{{ $conta->pedido_id }}</strong>.</p>

    <p>Forma de pagamento: {{ $forma_pagamento }}<br>
    Data da baixa: {{ $data_baixa }}</p>

    <div class="linha"></div>

    <p>Emitido automaticamente pelo sistema em {{ now()->format('d/m/Y H:i') }}.</p>

    <div class="assinatura">
        ___________________________________________<br>
        Assinatura do Recebedor
    </div>
</body>
</html>
