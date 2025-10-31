<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    use HasFactory;

    protected $table = 'appestoque';

    protected $fillable = [
        'produto_id',
        'codfabnumero',
        'estoque_gerencial',
        'reservado',
        'avaria',
        'ultimo_preco_compra',
        'ultimo_preco_venda',
        'data_ultima_mov',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
