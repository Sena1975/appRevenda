<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoKitItem extends Model
{
    use HasFactory;

    protected $table = 'appproduto_kit_itens';

    protected $fillable = [
        'kit_produto_id',
        'item_produto_id',
        'quantidade',
    ];

    public function produtoKit()
    {
        return $this->belongsTo(Produto::class, 'kit_produto_id');
    }

    public function produtoItem()
    {
        return $this->belongsTo(Produto::class, 'item_produto_id');
    }
}
