<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoCompra extends Model
{
    use HasFactory;

    protected $table = 'appcompra'; // nome real da tabela no banco

    protected $fillable = [
        'fornecedor_id',
        'data_compra',
        'data_emissao',
        'numpedcompra',
        'numero_nota',
        'valor_total',
        'preco_venda_total', 
        'pontostotal',
        'qtditens',
        'formapgto',
        'qt_parcelas',
        'status',
    ];

    // 🔗 Relacionamento com o fornecedor
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    // 🔗 Relacionamento com os itens da compra
    public function itens()
    {
        return $this->hasMany(ItensCompra::class, 'compra_id');
    }
}
