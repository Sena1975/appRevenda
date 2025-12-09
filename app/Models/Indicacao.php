<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Indicacao extends Model
{
    use HasFactory;

    protected $table = 'appindicacao';

    protected $fillable = [
        'empresa_id',
        'indicador_id',
        'indicado_id',
        'pedido_id',
        'campanha_id',
        'valor_pedido',
        'valor_premio',
        'status', // 'pendente' | 'pago'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

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

    // Campanha
    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeDaEmpresa($query, ?int $empresaId = null)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }

        $user    = Auth::user();
        $empresa = $user?->empresa;

        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        if ($empresa) {
            $query->where('empresa_id', $empresa->id);
        }

        return $query;
    }
}
