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
        'supervisor_id',
        'status',
    ];

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

    public function revendedoras()
    {
        return $this->hasMany(Revendedora::class, 'equipe_id');
    }
}
