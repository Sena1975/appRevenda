<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WhatsappConfig extends Model
{
    protected $table = 'appwhatsapp_config';

    protected $fillable = [
        'empresa_id',
        'provider',
        'phone_number',
        'nome_exibicao',
        'api_url',
        'api_key',
        'token',
        'instance_id',
        'is_default',
        'ativo',
        'extras',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'ativo'      => 'boolean',
        'extras'     => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function scopeDaEmpresa($query)
    {
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
}
