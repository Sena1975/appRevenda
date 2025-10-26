<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'appcliente';

    protected $fillable = [
    'nome',
    'cpf',
    'telefone',
    'whatsapp',
    'telegram',
    'instagram',
    'facebook',
    'email',
    'uf',
    'cidade',
    'endereco',
    'bairro',
    'data_nascimento',
    'timecoracao',
    'sexo',
    'filhos',
    'status',
    'foto',
    ];
}
