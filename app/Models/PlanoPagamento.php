<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoPagamento extends Model
{
    use HasFactory;

    protected $table = 'appplanopagamento';

    protected $fillable = [
        'codplano',
        'descricao',
        'formapagamento_id',
        'parcelas',
        'prazo1',
        'prazo2',
        'prazo3',
        'prazomedio',
        'ativo',
    ];

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    public $timestamps = true;

    public function formaPagamento()
    {
        return $this->belongsTo(\App\Models\FormaPagamento::class, 'formapagamento_id');
    }
}
