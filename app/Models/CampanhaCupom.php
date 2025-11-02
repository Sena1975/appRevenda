<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaCupom extends Model
{
    protected $table = 'appcampanha_cupom';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'campanha_id','cliente_id','codfabnumero','pedido_id','codigo_cupom',
        'tipo_geracao','valor_referencia','quantidade_referencia','data_geracao','utilizado'
    ];

    protected $casts = [
        'valor_referencia' => 'decimal:2',
        'quantidade_referencia' => 'integer',
        'utilizado' => 'boolean',
        'data_geracao' => 'datetime',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id', 'id');
    }

    // Helper para código único
    public static function gerarCodigo(): string
    {
        return strtoupper(bin2hex(random_bytes(5))); // ex.: 9F3A1C2BDE
    }
}
