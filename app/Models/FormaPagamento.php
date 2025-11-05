<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormaPagamento extends Model
{
    protected $table = 'appformapagamento';

    // Seus nomes de timestamp são diferentes dos padrões:
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
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
        'atualizado_em'=> 'datetime',
    ];

    /* Relacionamentos */
    public function planos()
    {
        return $this->hasMany(PlanoPagamento::class, 'formapagamento_id', 'id');
    }

    /* Scopes úteis */
    public function scopeAtivo($q) { return $q->where('ativo', 1); }
}
