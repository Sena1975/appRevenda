<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaCliente extends Model
{
    protected $table = 'appcampanha_cliente';
    protected $primaryKey = 'id';
    public $timestamps = false; // tabela usa 'atualizado_em' manualmente

    protected $fillable = [
        'campanha_id','cliente_id','saldo_valor','saldo_quantidade','total_cupons','atualizado_em'
    ];

    protected $casts = [
        'saldo_valor' => 'decimal:2',
        'saldo_quantidade' => 'integer',
        'total_cupons' => 'integer',
        'atualizado_em' => 'datetime',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id', 'id');
    }
}
