<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVenda extends Model
{
    use HasFactory;

    protected $table = 'appitemvenda';
    protected $primaryKey = 'id';

    // created_at customizado; sem updated_at
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'codfabnumero',
        'quantidade',        // int na prática (inteiro)
        'preco_unitario',
        'preco_total',
        'pontuacao',         // NOVO (pontos unitários)
        'pontuacao_total',   // NOVO (qtd * pontos)
        'reservado',
        'entregue',
    ];

    protected $casts = [
        'quantidade'       => 'integer',
        'preco_unitario'   => 'decimal:2',
        'preco_total'      => 'decimal:2',
        'pontuacao'        => 'integer',
        'pontuacao_total'  => 'integer',
        'reservado'        => 'boolean',
        'entregue'         => 'boolean',
        'criado_em'        => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id', 'id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id', 'id');
    }
    
    //Plugando campanhas no seu fluxo atual
    public function getValorTotalAttribute()
    {
        // Prioriza preco_total; se não houver, calcula pelo unitário * qtd
        if (isset($this->attributes['preco_total'])) {
            return (float) $this->attributes['preco_total'];
        }
        $unit = (float) ($this->attributes['preco_unitario'] ?? 0);
        $qtd  = (int)   ($this->attributes['quantidade'] ?? 0);
        return $unit * $qtd;
    }

}
