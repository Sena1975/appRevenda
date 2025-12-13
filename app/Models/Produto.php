<?php

namespace App\Models;

use App\Models\ProdutoKitItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

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
        'tipo',
        'empresa_id',
    ];
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function scopeDaEmpresa($query)
    {
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
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

    public function itensDoKit()
    {
        // Este produto é um KIT (tipo = 'K') e tem vários itens
        return $this->hasMany(ProdutoKitItem::class, 'kit_produto_id', 'id')
            ->with('produtoItem');
    }

    public function kitsOndeSouItem()
    {
        // Este produto é unitário (tipo = 'P') e pode estar em vários kits
        return $this->hasMany(ProdutoKitItem::class, 'item_produto_id', 'id')
            ->with('produtoKit');
    }

    public function scopeProdutos($query)
    {
        return $query->where('tipo', 'P');
    }

    public function scopeKits($query)
    {
        return $query->where('tipo', 'K');
    }
}
