<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

class PedidoCompra extends Model
{
    use HasFactory;

    protected $table = 'appcompra'; // nome real da tabela no banco

    protected $fillable = [
        'fornecedor_id',
        'data_compra',
        'data_emissao',
        'numpedcompra',
        'numero_nota',
        'valor_total',
        'valor_desconto',
        'valor_liquido',
        'preco_venda_total',
        'pontostotal',
        'qtditens',
        'formapgto',
        'forma_pagamento_id',
        'plano_pagamento_id',
        'qt_parcelas',
        'observacao',
        'status',
        'empresa_id',
    ];
    protected $casts = [
        'data_compra'  => 'date',
        'data_emissao' => 'date',
        'valor_total'  => 'decimal:2',
        'valor_liquido' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    // 🔗 Relacionamento com o fornecedor
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function scopeDaEmpresa($query)
    {
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }

    // 🔗 Relacionamento com os itens da compra
    public function itens()
    {
        return $this->hasMany(ItensCompra::class, 'compra_id');
    }

    public function contasPagar()
    {
        return $this->hasMany(ContasPagar::class, 'compra_id');
    }
}
