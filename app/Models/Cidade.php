<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cidade extends Model
{
    protected $table = 'appcidade';

    protected $fillable = [
        'nome',
        'codigoibge', // ✅ novo campo
        'uf_id',
    ];

    public function uf()
    {
        return $this->belongsTo(UF::class, 'uf_id');
    }

    public function bairros()
    {
        return $this->hasMany(Bairro::class, 'cidade_id');
    }
}
