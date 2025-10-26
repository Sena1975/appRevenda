<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'appfornecedor';

    protected $fillable = [
        'razaosocial',
        'nomefantasia',
        'cnpj',
        'pessoacontato',
        'telefone',
        'whatsapp',
        'telegram',
        'instagram',
        'facebook',
        'email',
        'endereco',
        'status',
    ];
}
