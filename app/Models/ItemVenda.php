<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVenda extends Model
{
    use HasFactory;

    protected $table = 'appitemvenda';
    protected $primaryKey = 'id';

    // Apenas created_at customizado; sem updated_at
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'codfabnumero',
        'quantidade',       // decimal(10,3)
        'preco_unitario',   // decimal(10,2)
        'preco_total',      // decimal(10,2)
        'reservado',        // tinyint(1)
        'entregue',         // tinyint(1)
        'pontuacao',
        'pontuacao_total',
    ];

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'preco_unitario' => 'decimal:2',
        'preco_total'    => 'decimal:2',
        'reservado'      => 'boolean',
        'entregue'       => 'boolean',
        'criado_em'      => 'datetime',
        'pontuacao'       => 'integer',
        'pontuacao_total' => 'integer',
    ];

    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id', 'id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id', 'id');
    }
}
