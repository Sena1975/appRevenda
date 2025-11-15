<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PedidoCompra; // 👈 importa o model certo

class ContasPagar extends Model
{
    use HasFactory;

    protected $table = 'appcontaspagar';
    protected $primaryKey = 'id';

    public $timestamps = true;
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'compra_id',
        'numero_nota',
        'fornecedor_id',
        'parcela',
        'total_parcelas',
        'forma_pagamento_id',
        'data_emissao',
        'data_vencimento',
        'valor',
        'status',
        'data_pagamento',
        'valor_pago',
        'nosso_numero',
        'observacao',
    ];

    protected $casts = [
        'data_emissao'    => 'date',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
        'valor'           => 'decimal:2',
        'valor_pago'      => 'decimal:2',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function formaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }

    public function compra()
    {
        return $this->belongsTo(PedidoCompra::class, 'compra_id');
    }

    public function baixas()
    {
        return $this->hasMany(BaixaPagar::class, 'conta_id');
    }
}
