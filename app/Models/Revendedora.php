<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;

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
        'empresa_id',
    ];
    protected $casts = [
        'revenda_padrao' => 'boolean',
        'status'         => 'integer',
    ];
}
