<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoPagamento extends Model
{
    protected $table = 'appplanopagamento';

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'codplano',         // único
        'descricao',
        'formapagamento_id',
        'parcelas',
        'prazo1',
        'prazo2',
        'prazo3',
        'prazomedio',
        'ativo',            // 0/1
    ];

    protected $casts = [
        'formapagamento_id' => 'integer',
        'parcelas'          => 'integer',
        'prazo1'            => 'integer',
        'prazo2'            => 'integer',
        'prazo3'            => 'integer',
        'prazomedio'        => 'integer',
        'ativo'             => 'boolean',
        'criado_em'         => 'datetime',
        'atualizado_em'     => 'datetime',
    ];

    /* Relacionamentos */
public function formaPagamento()
{
    // Reaproveita o relacionamento já definido
    return $this->belongsTo(\App\Models\FormaPagamento::class, 'formapagamento_id', 'id');
}
    /* Scopes úteis */
    public function scopeAtivo($q) { return $q->where('ativo', 1); }
}
