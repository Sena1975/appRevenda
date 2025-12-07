<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'appcliente';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'whatsapp',
        'botconversa_subscriber_id',
        'telegram',
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
        'origem_cadastro',
        'foto',
        'empresa_id',
    ];


    // Formatações automáticas
    protected $casts = [
        'data_nascimento' => 'date',
    ];

    // app/Models/Cliente.php

    public function getFotoUrlAttribute()
    {
        // Se tiver caminho de foto gravado, usa storage
        if (!empty($this->foto)) {
            return asset('storage/' . ltrim($this->foto, '/'));
        }

        // Se não tiver foto, usa avatar padrão em public/images
        return asset('images/avatar-cliente.png'); // ou .svg, veja o nome certo aí
    }


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

    public function getWhatsappLinkAttribute()
    {
        // Se não tiver WhatsApp cadastrado, não gera link
        if (!$this->whatsapp) {
            return null;
        }

        // Garante que tenha só dígitos
        $numero = preg_replace('/\D+/', '', $this->whatsapp);

        // Se não começar com 55, prefixa o DDI do Brasil
        if (!str_starts_with($numero, '55')) {
            $numero = '55' . $numero;
        }

        // 🔹 TEXTO PERSONALIZADO DA MENSAGEM
        // Pode trocar essa frase como quiser
        $texto = "Olá {$this->nome}, tudo bem? Aqui é a sua consultora de beleza 😊, por favor, efetue seu cadastro pra digitar seu pedido";

        // Monta query string (já faz o encode dos espaços, acentos etc.)
        $params = http_build_query([
            'text' => $texto,
        ]);

        // URL final do WhatsApp
        return "https://wa.me/{$numero}?{$params}";
    }

    public function getWhatsappIndicacaoLinkAttribute()
    {
        // Se não tiver WhatsApp cadastrado, não gera link
        if (!$this->whatsapp) {
            return null;
        }

        // Garante que tenha só dígitos
        $numero = preg_replace('/\D+/', '', $this->whatsapp);

        // Se não começar com 55, prefixa o DDI do Brasil
        if (!str_starts_with($numero, '55')) {
            $numero = '55' . $numero;
        }

        // 🔹 Link público de cadastro com o ID deste cliente como indicador
        // Certifique-se de que essa rota existe: route('clientes.public.create')
        $linkIndicacao = route('clientes.public.create', [
            'indicador' => $this->id,
        ]);

        // 🔹 TEXTO DA MENSAGEM QUE VAI NO WHATSAPP
        $texto = "Olá {$this->nome}, tudo bem? 😊\n"
            . "Esse é o SEU link de indicação para cadastrar suas amigas:\n"
            . "{$linkIndicacao}\n\n"
            . "Sempre que alguém se cadastrar por esse link e fizer a primeira compra, "
            . "você participa da campanha de indicação. 🎁";

        // Monta query string (faz encode dos caracteres especiais)
        $params = http_build_query([
            'text' => $texto,
        ]);

        // URL final do WhatsApp
        return "https://wa.me/{$numero}?{$params}";
    }

    public function indicador()
    {
        return $this->belongsTo(Cliente::class, 'indicador_id');
    }

        public function scopeDaEmpresa($query)
    {
        $user = Auth::user();

        if ($user) {
            return $query->where('empresa_id', $user->empresa_id);
        }

        return $query;
    }
}
