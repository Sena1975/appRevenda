<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaTipo extends Model
{
    protected $table = 'appcampanha_tipo'; // nome EXATO da tabela

    public $timestamps = false;

    protected $fillable = [
        'descricao',
    ];
}
