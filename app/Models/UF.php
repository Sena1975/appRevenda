<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UF extends Model
{
    protected $table = 'appuf';

    protected $fillable = ['sigla', 'nome'];

    public function cidades()
    {
        return $this->hasMany(Cidade::class, 'uf_id');
    }
}