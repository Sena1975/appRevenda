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
        'codnotafiscal',
        'ean',
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
    // app/Models/Produto.php

    public function precos()
    {
        // todos os registros de tabela de preço do produto
        return $this->hasMany(\App\Models\TabelaPreco::class, 'produto_id', 'id');
    }

    public function precoVigente()
    {
        // preço vigente (status=1 e dentro do intervalo), pegando o mais recente por data_inicio
        return $this->hasOne(\App\Models\TabelaPreco::class, 'produto_id', 'id')
            ->vigente()
            ->latestOfMany('data_inicio');
    }
}
