<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ContasReceber;

class BaixaReceber extends Model
{
    use HasFactory;

    protected $table = 'appbaixa_receber';

    protected $fillable = [
        'conta_id',
        'data_baixa',
        'valor_baixado',
        'forma_pagamento',
        'observacao',
        'recibo_enviado'
    ];

    public function conta()
    {
        return $this->belongsTo(ContasReceber::class, 'conta_id');
    }
}
