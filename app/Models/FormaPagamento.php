<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class FormaPagamento extends Model
{
    protected $table = 'appformapagamento';

    // Seus nomes de timestamp são diferentes dos padrões:
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'empresa_id',
        'nome',
        'gera_receber',   // bool (0/1)
        'max_parcelas',   // int
        'ativo',          // bool (0/1)
    ];

    protected $casts = [
        'gera_receber' => 'boolean',
        'max_parcelas' => 'integer',
        'ativo'        => 'boolean',
        'criado_em'    => 'datetime',
        'atualizado_em' => 'datetime',
    ];

    /* Relacionamentos */
    public function planos()
    {
        return $this->hasMany(PlanoPagamento::class, 'formapagamento_id', 'id');
    }
    public function contasPagar()
    {
        return $this->hasMany(ContasPagar::class, 'forma_pagamento_id');
    }

    /* Scopes úteis */
    public function scopeAtivo($q)
    {
        return $q->where('ativo', 1);
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Escopo para filtrar por empresa logada.
     */
    public function scopeDaEmpresa($query, ?int $empresaId = null)
    {
        $empresaId = $empresaId ?? Auth::user()?->empresa_id;

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        return $query;
    }
}
