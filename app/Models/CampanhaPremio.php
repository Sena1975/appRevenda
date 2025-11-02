<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaPremio extends Model
{
    protected $table = 'appcampanha_premio';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'campanha_id','faixa_inicio','faixa_fim','valor_premio'
    ];

    protected $casts = [
        'faixa_inicio' => 'decimal:2',
        'faixa_fim' => 'decimal:2',
        'valor_premio' => 'decimal:2',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id', 'id');
    }
}
