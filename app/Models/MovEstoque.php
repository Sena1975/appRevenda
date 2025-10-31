<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovEstoque extends Model
{
    use HasFactory;

    protected $table = 'appmovestoque';

    protected $fillable = [
        'produto_id',
        'codfabnumero',
        'tipo_mov',
        'origem',
        'origem_id',
        'data_mov',
        'quantidade',
        'preco_unitario',
        'observacao',
        'status',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
