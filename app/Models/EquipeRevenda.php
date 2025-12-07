<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class EquipeRevenda extends Model
{
    use HasFactory;

    protected $table = 'appequiperevenda';

    protected $fillable = [
        'nome',
        'descricao',
        'revendedora_id',
        'status',
        'empresa_id'
    ];

    public function revendedora()
    {
        return $this->belongsTo(\App\Models\Revendedora::class, 'revendedora_id');
    }
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function scopeDaEmpresa($query)
    {
        /** @var \App\Models\AppUsuario|null $user */
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
}
