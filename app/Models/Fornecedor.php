<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

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
        'empresa_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
  
        public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    // opcional: scope para filtrar fornecedores da empresa atual
    public function scopeDaEmpresa($query)
    {
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
    
    public function contasPagar()
    {
        return $this->hasMany(ContasPagar::class, 'fornecedor_id');
    }
}
