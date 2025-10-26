<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipeRevenda extends Model
{
    use HasFactory;

    protected $table = 'appequiperevenda';

    protected $fillable = [
        'nome',
        'descricao',
        'revendedora_id',
        'status'
    ];

    public function revendedora()
    {
        return $this->belongsTo(\App\Models\Revendedora::class, 'revendedora_id');
    }
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

}
