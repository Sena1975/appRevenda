{{-- resources/views/Financeiro/recibo.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Recibo {{ ($recibo_numero ?? $reciboNumero ?? '---') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{ font-family: Arial, Helvetica, sans-serif; color:#222; margin:24px; }
    .h1{ font-size:20px; font-weight:700; margin-bottom:4px; }
    .muted{ color:#666; font-size:12px; }
    .box{ border:1px solid #ddd; border-radius:8px; padding:16px; margin-top:12px; }
    .row{ display:flex; gap:16px; margin-bottom:8px; }
    .col{ flex:1; }
    .right{ text-align:right; }
    .badge{ display:inline-block; padding:2px 8px; border-radius:20px; background:#f3f4f6; font-size:12px; }
    .total{ font-size:18px; font-weight:700; }
    .mt2{ margin-top:8px; } .mt3{ margin-top:12px; } .mt4{ margin-top:16px; }
    hr{ border:0; border-top:1px solid #eee; margin:14px 0; }
  </style>
</head>
<body>
  @php
    $c = $conta ?? null;
    $num = $recibo_numero ?? $reciboNumero ?? ('RCB-'.($c->id ?? '---'));
  @endphp

  <div class="row" style="align-items:center;">
    <div class="col">
      <div class="h1">Recibo</div>
      <div class="muted">Nº {{ $num }}</div>
    </div>
    <div class="col right">
      <div class="badge">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
  </div>

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
        @if(!empty($c->data_vencimento))
          {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
        @else
          &ndash;
        @endif
      </div>
      <div class="col">
        <strong>Pagamento:</strong><br>
        @if(!empty($c->data_pagamento))
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
        {{ strtoupper((string)$c->status) === 'PAGO' ? 'PAGOS'  : strtoupper((string)$c->status) }}
      </div>
      <div class="col right">
        <div class="muted">Valor do título</div>
        <div class="total">R$ {{ number_format((float)($c->valor ?? 0), 2, ',', '.') }}</div>

        @if(!is_null($c->valor_pago))
          <div class="muted mt2">Valor pago</div>
          <div class="total">R$ {{ number_format((float)$c->valor_pago, 2, ',', '.') }}</div>
        @endif
      </div>
    </div>

    @if(!empty($c->observacao))
      <div class="mt3">
        <strong>Observações:</strong>
        <div class="muted" style="white-space:pre-wrap;">{{ $c->observacao }}</div>
      </div>
    @endif
  </div>

  <div class="muted mt4">
    * Documento gerado pelo sistema em {{ now()->format('d/m/Y H:i') }}.
  </div>
</body>
</html>
