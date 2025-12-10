<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaMensagem extends Model
{
    protected $table = 'appcampanha_mensagem';

    protected $fillable = [
        'campanha_id',
        'mensagem_modelo_id',
        'evento',
        'delay_minutos',
        'ativo',
        'condicoes',
    ];

    protected $casts = [
        'ativo'      => 'boolean',
        'condicoes'  => 'array',
        'delay_minutos' => 'integer',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id');
    }

    public function modelo()
    {
        return $this->belongsTo(MensagemModelo::class, 'mensagem_modelo_id');
    }
}
