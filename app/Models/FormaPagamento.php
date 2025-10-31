<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPagamento extends Model
{
    use HasFactory;

    protected $table = 'appformapagamento';

    protected $fillable = [
        'nome',
        'gera_receber',
        'max_parcelas',
        'ativo',
    ];
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    /**
     * Retorna se essa forma de pagamento gera contas a receber
     */
    public function geraFinanceiro(): bool
    {
        return (bool) $this->gera_receber;
    }

    /**
     * Define um escopo para trazer apenas as formas ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativo', 1);
    }

    /**
     * Retorna texto formatado da forma (ex: PIX - até 1x)
     */
    public function getDescricaoCompletaAttribute(): string
    {
        $parcelas = $this->max_parcelas > 1 ? "até {$this->max_parcelas}x" : "à vista";
        return "{$this->nome} ({$parcelas})";
    }
}
