<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
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

    protected $casts = [
        'status' => 'boolean',
    ];
    public function contasPagar()
    {
        return $this->hasMany(ContasPagar::class, 'fornecedor_id');
    }
}
