<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaTipo extends Model
{
    protected $table = 'appcampanha_tipo'; 

    public $timestamps = false;

    protected $fillable = [
        'descricao',
    ];
}
