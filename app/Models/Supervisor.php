<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $table = 'appsupervisor';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',
        'email',
        'cep',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'status',
    ];

    public function equipes()
    {
        return $this->hasMany(EquipeRevenda::class, 'supervisor_id');
    }

}
