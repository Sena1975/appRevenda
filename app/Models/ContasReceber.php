<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContasReceber extends Model
{
    protected $table = 'appcontasreceber';
    protected $primaryKey = 'id';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'pedido_id',
        'cliente_id',
        'revendedora_id',
        'parcela',
        'total_parcelas',
        'forma_pagamento_id',
        'plano_pagamento_id',
        'data_emissao',
        'data_vencimento',
        'valor',
        'status',
        'data_pagamento',
        'valor_pago',
        'nosso_numero',
        'observacao',
        'criado_em',
        'atualizado_em'
    ];

    protected $casts = [
        'data_emissao'    => 'date',
        'data_vencimento' => 'date',
        // sem cast em data_pagamento para manter 'Y-m-d' como string (evita hora)
        // sem cast em valor_pago para não envolver float/rounding
        'valor'           => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }
    public function forma()
    {
        return $this->belongsTo(\App\Models\FormaPagamento::class, 'forma_pagamento_id');
    }
}
