<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'appcliente';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',   // << ADICIONEI
        'telegram',   // << ADICIONEI
        'cep',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'instagram',
        'facebook',
        'email',
        'data_nascimento',
        'timecoracao',
        'sexo',
        'filhos',
        'status',
        'foto'
    ];


    // Formatações automáticas
protected $casts = [
    'data_nascimento' => 'date',
];


    // Garante e-mail em minúsculas e sem espaços
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ? mb_strtolower(trim($value)) : null;
    }
    public function setWhatsappAttribute($value)
    {
        $this->attributes['whatsapp'] = $value ? preg_replace('/\D+/', '', $value) : null;
    }
    public function setTelegramAttribute($value)
    {
        if (!$value) {
            $this->attributes['telegram'] = null;
            return;
        }
        $v = trim($value);
        if (str_starts_with($v, '@')) $v = substr($v, 1);
        $this->attributes['telegram'] = mb_strtolower($v);
    }
    // (Opcional) normaliza telefone removendo caracteres não numéricos
    public function setTelefoneAttribute($value)
    {
        $onlyDigits = $value ? preg_replace('/\D+/', '', $value) : null;
        $this->attributes['telefone'] = $onlyDigits;
    }
    public function setInstagramAttribute($value)
    {
        if (!$value) {
            $this->attributes['instagram'] = null;
            return;
        }

        $v = trim($value);

        // remove @ inicial
        if (str_starts_with($v, '@')) {
            $v = substr($v, 1);
        }

        // se vier URL, tenta extrair o path
        // exemplos aceitos: https://instagram.com/user, https://www.instagram.com/user/
        $v = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $v);
        $v = preg_replace('#/$#', '', $v); // tira barra final

        // normaliza para minúsculas
        $v = mb_strtolower($v);

        // regra simples de username do Instagram (letras, números, ponto e _)
        // limite típico: 30 chars
        if (!preg_match('/^[a-z0-9._]{1,30}$/', $v)) {
            // se não casar, guarda bruto mesmo (ou defina como null, se preferir)
            // $v = null;
        }

        $this->attributes['instagram'] = $v ?: null;
    }
}
