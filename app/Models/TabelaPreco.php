<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabelapreco extends Model
{
    use HasFactory;

    protected $table = 'apptabelapreco';

    protected $fillable = [
        'produto_id',
        'codfab',  
        'preco_compra',
        'preco_revenda',
        'pontuacao',
        'data_inicio',
        'data_fim',
        'status'
    ];

    // Relacionamento com o produto
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
