<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicacao extends Model
{
    use HasFactory;

    protected $table = 'appindicacao';

    protected $fillable = [
        'indicador_id',
        'indicado_id',
        'pedido_id',
        'valor_pedido',
        'valor_premio',
        'status', // 'pendente' | 'pago'
    ];

    // Quem indicou (cliente que recebe o PIX)
    public function indicador()
    {
        return $this->belongsTo(Cliente::class, 'indicador_id', 'id');
    }

    // Cliente indicado (que fez a compra)
    public function indicado()
    {
        return $this->belongsTo(Cliente::class, 'indicado_id', 'id');
    }

    // Pedido da primeira compra
    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id', 'id');
    }

    // Campanhas
    public function campanha()
    {
        return $this->belongsTo(\App\Models\Campanha::class, 'campanha_id');
    }
}
