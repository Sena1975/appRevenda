<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaixaPagar extends Model
{
    use HasFactory;

    protected $table = 'appbaixa_pagar';

    protected $primaryKey = 'id';

    // Só temos criado_em, então vou deixar o timestamps false
    public $timestamps = false;

    protected $fillable = [
        'conta_id',
        'numero_nota',
        'parcela',
        'data_baixa',
        'valor_baixado',
        'forma_pagamento',
        'observacao',
        'recibo_enviado',
    ];

    protected $casts = [
        'data_baixa'     => 'date',
        'valor_baixado'  => 'decimal:2',
        'recibo_enviado' => 'boolean',
    ];

    /*
     |--------------------------------------------------------------------------
     | RELACIONAMENTOS
     |--------------------------------------------------------------------------
     */

    // Conta principal (parcela) à qual essa baixa pertence
    public function conta()
    {
        return $this->belongsTo(ContasPagar::class, 'conta_id');
    }
}
