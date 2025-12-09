<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MovEstoque extends Model
{
    protected $table = 'appmovestoque';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empresa_id',
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

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
    public function scopeDaEmpresa($query)
    {
        $user    = Auth::user();
        $empresa = $user?->empresa;

        // fallback: se o middleware registrou a empresa no container
        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        if ($empresa) {
            $query->where('empresa_id', $empresa->id);
        }

        return $query;
    }
}
