<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Categoria extends Model
{
    protected $table = 'appcategoria';

    protected $fillable = [
        // seus campos...
        'nome', // ou 'descricao' (ajuste)
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

    public function scopeDaEmpresa($query)
    {
        $user = Auth::user();
        if ($user?->empresa_id) {
            return $query->where('empresa_id', $user->empresa_id);
        }
        return $query;
    }
}
