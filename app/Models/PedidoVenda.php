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
        'plano_pagamento_id',
        'codplano',              // NOVO
        'campanha_id',
        'camp_sugerida_id',
        'indicador_id',
        'confirmacao_usuario',
        'valor_total',
        'valor_desconto',
        'valor_liquido',
        'pontuacao',             // NOVO (soma dos pontos unitários)
        'pontuacao_total',       // NOVO (soma de qtd x pontos)
        'observacao',
    ];

    protected $casts = [
        'data_pedido'         => 'date',
        'previsao_entrega'    => 'date',
        'confirmacao_usuario' => 'boolean',
        'valor_total'         => 'decimal:2',
        'valor_desconto'      => 'decimal:2',
        'valor_liquido'       => 'decimal:2',
        'pontuacao'           => 'integer',
        'pontuacao_total'     => 'integer',
        'criado_em'           => 'datetime',
        'atualizado_em'       => 'datetime',
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

    public function plano()
    {
        return $this->belongsTo(PlanoPagamento::class, 'plano_pagamento_id', 'id');
    }
}
