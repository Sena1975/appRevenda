<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'appproduto';

    protected $fillable = [
        'codfab',
        'codfabtexto',
        'codfabnumero',
        'nome',
        'descricao',
        'imagem',
        'categoria_id',
        'subcategoria_id',
        'fornecedor_id',
        'status',
        'preco_revenda',
        'preco_compra',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
