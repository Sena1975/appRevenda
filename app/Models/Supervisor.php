<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $table = 'appsupervisor';

    protected $fillable = [
        'nome',
        'telefone',
        'whatsapp',
        'telegram',
        'instagram',
        'facebook',
        'email',
        'datanascimento',
        'status',
    ];
}
