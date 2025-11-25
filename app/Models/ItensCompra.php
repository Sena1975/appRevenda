<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItensCompra extends Model
{
    use HasFactory;

    protected $table = 'appcompraproduto'; // nome real da tabela no banco

    protected $fillable = [
        'compra_id',
        'produto_id',
        'quantidade',
        'preco_unitario',
        'preco_venda_unitario', 
        'preco_venda_total',
        'total_item',
        'pontos',
        'pontostotal',
        'qtd_disponivel',
        'tipo_item', 
    ];

    // 🔗 Cada item pertence a uma compra
    public function compra()
    {
        return $this->belongsTo(PedidoCompra::class, 'compra_id');
    }

    // 🔗 Cada item pertence a um produto
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
