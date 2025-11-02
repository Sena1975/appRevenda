<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaTipo extends Model
{
    protected $table = 'appcampanha_tipo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['descricao'];

    public function campanhas()
    {
        return $this->hasMany(Campanha::class, 'tipo_id', 'id');
    }
}
