<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revendedora extends Model
{
    use HasFactory;

    protected $table = 'apprevendedora';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',
        'telegram',
        'instagram',
        'facebook',
        'email',
        'endereco',
        'equipe_id',
        'supervisor_id',
        'datanascimento',
        'status',
    ];

    public function equipe()
    {
        return $this->belongsTo(EquipeRevenda::class, 'equipe_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
}
