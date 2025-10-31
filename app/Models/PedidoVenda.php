<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoVenda extends Model
{
    use HasFactory;

    protected $table = 'apppedidovenda';
    protected $primaryKey = 'id';

    // Timestamps customizados
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'cliente_id',
        'revendedora_id',
        'data_pedido',
        'previsao_entrega',
        'status',                 // 'PENDENTE','ENTREGUE','CANCELADO'
        'forma_pagamento_id',
        'campanha_id',
        'camp_sugerida_id',
        'indicador_id',
        'confirmacao_usuario',
        'valor_total',
        'valor_desconto',
        'valor_liquido',
        'observacao',
        'codplano',
        'pontuacao',
        'pontuacao_total',
    ];

    protected $casts = [
        'data_pedido'       => 'date',
        'previsao_entrega'  => 'date',
        'confirmacao_usuario' => 'boolean',
        'valor_total'       => 'decimal:2',
        'valor_desconto'    => 'decimal:2',
        'valor_liquido'     => 'decimal:2',
        'criado_em'         => 'datetime',
        'atualizado_em'     => 'datetime',
        'pontuacao'       => 'integer',
        'pontuacao_total' => 'integer',
    ];

    // Relacionamentos
    public function itens()
    {
        return $this->hasMany(ItemVenda::class, 'pedido_id', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }

    public function revendedora()
    {
        return $this->belongsTo(Revendedora::class, 'revendedora_id', 'id');
    }

    public function forma()
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id', 'id');
    }

    // public function campanha()
    // {
    //     return $this->belongsTo(Campanha::class, 'campanha_id', 'id');
    // }

    public function indicador()
    {
        return $this->belongsTo(Cliente::class, 'indicador_id', 'id');
    }
}
