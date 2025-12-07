<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensagemModelo extends Model
{
    protected $table = 'appmensagem_modelo';

    protected $fillable = [
        'codigo',
        'nome',
        'canal',
        'conteudo',
        'ativo',
    ];
}
