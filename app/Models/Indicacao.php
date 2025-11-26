<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicacao extends Model
{
    protected $table = 'appindicacao';

    protected $fillable = [
        'indicador_id',
        'indicado_id',
        'pedido_id',
        'valor_pedido',
        'valor_premio',
        'status',
        'data_pagamento',
    ];

    // Quem indicou (cliente "antigo")
    public function indicador()
    {
        return $this->belongsTo(Cliente::class, 'indicador_id');
    }

    // Cliente indicado (cliente "novo")
    public function indicado()
    {
        return $this->belongsTo(Cliente::class, 'indicado_id');
    }

    // Pedido que gerou o prêmio
    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id');
    }
}
