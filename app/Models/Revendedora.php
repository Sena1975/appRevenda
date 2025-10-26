<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revendedora extends Model
{
    use HasFactory;

    protected $table = 'apprevendedora';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',
        'email',
        'endereco',
        'cidade',
        'estado',
        'status'
    ];
}
