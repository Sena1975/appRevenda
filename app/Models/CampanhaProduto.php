<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaProduto extends Model
{
    protected $table = 'appcampanha_produto';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'campanha_id','produto_id','codfabnumero','categoria_id',
        'quantidade_minima','peso_participacao','observacao'
    ];

    protected $casts = [
        'quantidade_minima' => 'integer',
        'peso_participacao' => 'decimal:2',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id', 'id');
    }

    public function produto()
    {
        return $this->belongsTo(\App\Models\Produto::class, 'produto_id', 'id');
    }

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class, 'categoria_id', 'id');
    }
}
