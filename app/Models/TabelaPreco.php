<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabelaPreco extends Model
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
        'status',
        'codnotafiscal',
        'ean',
    ];

    protected $casts = [
        'preco_compra'  => 'decimal:2',
        'preco_revenda' => 'decimal:2',
        'pontuacao'     => 'integer',
        'data_inicio'   => 'date',
        'data_fim'      => 'date',
        'status'        => 'boolean',
    ];

    // Relacionamento com Produto
    public function produto()
    {
        return $this->belongsTo(\App\Models\Produto::class, 'produto_id', 'id');
    }

    // Escopo para pegar preços vigentes e ativos hoje
    public function scopeVigente($query)
    {
        $hoje = now()->toDateString();
        return $query->where('status', 1)
                     ->where('data_inicio', '<=', $hoje)
                     ->where('data_fim', '>=', $hoje);
    }
}
