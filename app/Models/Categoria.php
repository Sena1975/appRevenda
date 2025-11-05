<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'appcategoria';

    protected $fillable = ['nome','categoria','status'];

    protected $casts = ['status'=>'boolean'];

    public function subcategorias()
    {
        return $this->hasMany(\App\Models\Subcategoria::class, 'categoria_id', 'id');
    }
}
