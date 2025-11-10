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
        'cep',
        'telefone',
        'whatsapp',
        'telegram',
        'instagram',
        'facebook',
        'email',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'equipe_id',
        'supervisor_id',
        'datanascimento',
        'status',
        'revenda_padrao',
    ];
    protected $casts = [
        'revenda_padrao' => 'boolean',
        'status'         => 'integer',
    ];
}
