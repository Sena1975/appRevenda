<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'appempresas';

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'documento',
        'email_contato',
        'telefone',
        'slug',
        'plano',
        'ativo',
    ];

    // Uma empresa tem vários usuários
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'empresa_id');
    }
}
