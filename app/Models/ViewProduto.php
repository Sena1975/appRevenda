<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewProduto extends Model
{
    // nome minúsculo da view
    protected $table = 'view_app_produtos';

    // chave primária minúscula
    protected $primaryKey = 'codigo_fabrica';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    // somente-leitura
    public function save(array $options = [])
    {
        throw new \LogicException('ViewProduto é somente leitura.');
    }
    public function delete()
    {
        throw new \LogicException('ViewProduto é somente leitura.');
    }

    protected $casts = [
        'preco_revenda'        => 'decimal:2',
        'preco_compra'         => 'decimal:2',
        'pontos'               => 'integer',
        'qtd_estoque'          => 'integer',
        'data_ultima_entrada'  => 'datetime',
    ];
}
