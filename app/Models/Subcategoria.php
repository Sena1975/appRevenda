<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $table = 'appsubcategoria';

    protected $fillable = ['nome','categoria_id','subcategoria','status'];

    protected $casts = ['status'=>'boolean'];

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class, 'categoria_id', 'id');
    }
}
