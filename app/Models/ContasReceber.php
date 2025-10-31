<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\PedidoVenda;
use App\Models\FormaPagamento;
use App\Models\BaixaReceber;

class ContasReceber extends Model
{
    use HasFactory;

    protected $table = 'appcontasreceber';

    protected $fillable = [
        'pedido_id',
        'cliente_id',
        'revendedora_id',
        'parcela',
        'total_parcelas',
        'forma_pagamento_id',
        'data_emissao',
        'data_vencimento',
        'data_pagamento',
        'valor',
        'valor_pago',
        'status',
        'observacao'
    ];

    /* ============================
     * RELACIONAMENTOS
     * ============================ */

    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function revendedora()
    {
        return $this->belongsTo(Revendedora::class, 'revendedora_id');
    }

    public function formaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }

    public function baixas()
    {
        return $this->hasMany(BaixaReceber::class, 'conta_id');
    }

    /* ============================
     * MÉTODOS ÚTEIS
     * ============================ */

    // Retorna o saldo a receber (valor - valor pago)
    public function getSaldoAttribute()
    {
        $pago = $this->valor_pago ?? 0;
        return $this->valor - $pago;
    }

    // Retorna se o título está vencido
    public function getVencidoAttribute()
    {
        return $this->status == 'ABERTO' && now()->gt($this->data_vencimento);
    }
}
