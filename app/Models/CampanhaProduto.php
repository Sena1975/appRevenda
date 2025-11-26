<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampanhaProduto extends Model
{
    use HasFactory;

    /**
     * Nome da tabela no banco.
     * Ajuste aqui se o nome estiver diferente.
     */
    protected $table = 'appcampanhaproduto';

    /**
     * Campos que podem ser preenchidos em massa (create/update).
     */
    protected $fillable = [
        'campanha_id',
        'produto_id',
        'codfabnumero',
        'categoria_id',
        'quantidade_minima',
        'peso_participacao',
        'observacao',
    ];

    /**
     * Casts de tipos para facilitar uso.
     */
    protected $casts = [
        'campanha_id'       => 'integer',
        'produto_id'        => 'integer',
        'categoria_id'      => 'integer',
        'quantidade_minima' => 'integer',
        'peso_participacao' => 'float',
    ];

    /**
     * Relacionamentos
     */

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
