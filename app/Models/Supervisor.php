<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $table = 'appsupervisor';

    protected $fillable = [
        'nome','cpf','telefone','whatsapp','email',
        'instagram','facebook',
        'cep','endereco','bairro','cidade','estado',
        'datanascimento','status',
    ];

    protected $casts = [
        'status'         => 'boolean',
        'datanascimento' => 'date',
    ];
}
