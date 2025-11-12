<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Produto;

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

    protected $casts = [
        'estoque_gerencial' => 'decimal:3',
        'reservado'         => 'decimal:3',
        'avaria'            => 'decimal:3',
        'disponivel'        => 'decimal:3',
        'ultimo_preco_compra' => 'decimal:2',
        'ultimo_preco_venda'  => 'decimal:2',
        'data_ultima_mov'     => 'datetime',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
