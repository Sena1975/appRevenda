<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Supervisor extends Model
{
    protected $table = 'appsupervisor';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',
        'email',
        'instagram',
        'facebook',
        'cep',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'datanascimento',
        'status',
        'empresa_id',
    ];

    protected $casts = [
        'status'         => 'boolean',
        'datanascimento' => 'date',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function scopeDaEmpresa($query)
    {
        /** @var \App\Models\AppUsuario|null $user */
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
}
