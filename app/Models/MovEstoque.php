<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovEstoque extends Model
{
    protected $table = 'appmovestoque';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'produto_id',
        'codfabnumero',
        'tipo_mov',       // ENTRADA | SAIDA
        'origem',         // COMPRA | VENDA | AJUSTE | DEVOLUCAO
        'origem_id',
        'data_mov',
        'quantidade',     // pode ser negativa na saída
        'preco_unitario',
        'observacao',
        'status',
    ];

    protected $casts = [
        'data_mov' => 'datetime',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
