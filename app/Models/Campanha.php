<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Campanha extends Model
{
    protected $table = 'appcampanha';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Campos de timestamp customizados
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'metodo_php',
        'tipo_id',
        'data_inicio',
        'data_fim',
        'ativa',
        'cumulativa',
        'aplicacao_automatica',
        'prioridade',
        'perc_desc',
        'valor_base_cupom',
        'acumulativa_por_valor',
        'acumulativa_por_quantidade',
        'quantidade_minima_cupom',
        'tipo_acumulacao',
        'produto_brinde_id'
    ];

    protected $casts = [
        'empresa_id'                  => 'integer',
        'ativa'                       => 'boolean',
        'cumulativa'                  => 'boolean',
        'aplicacao_automatica'        => 'boolean',
        'acumulativa_por_valor'       => 'boolean',
        'acumulativa_por_quantidade'  => 'boolean',
        'data_inicio'                 => 'date',
        'data_fim'                    => 'date',
        'valor_base_cupom'            => 'decimal:2',
        'prioridade'                  => 'integer',
        'quantidade_minima_cupom'     => 'integer',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function mensagensConfiguradas()
    {
        return $this->hasMany(CampanhaMensagem::class, 'campanha_id');
    }

    public function tipo()
    {
        return $this->belongsTo(CampanhaTipo::class, 'tipo_id', 'id');
    }

    public function premios()
    {
        return $this->hasMany(CampanhaPremio::class, 'campanha_id', 'id');
    }

    public function produtos()
    {
        return $this->hasMany(CampanhaProduto::class, 'campanha_id', 'id');
    }

    public function clientes()
    {
        return $this->hasMany(CampanhaCliente::class, 'campanha_id', 'id');
    }

    public function cupons()
    {
        return $this->hasMany(CampanhaCupom::class, 'campanha_id', 'id');
    }

    public function indicacoes()
    {
        return $this->hasMany(\App\Models\Indicacao::class, 'campanha_id');
    }

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

    // Regras auxiliares
    public function emVigencia(): bool
    {
        $hoje = now()->toDateString();
        return $this->ativa
            && $this->data_inicio->toDateString() <= $hoje
            && $hoje <= $this->data_fim->toDateString();
    }

    public function isCumulativa(): bool
    {
        return (bool) $this->cumulativa;
    }
}
