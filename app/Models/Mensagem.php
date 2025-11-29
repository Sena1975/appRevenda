<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Mensagem extends Model
{
    protected $table = 'appmensagens';

    protected $fillable = [
        'cliente_id',
        'pedido_id',
        'campanha_id',
        'canal',
        'direcao',
        'tipo',
        'conteudo',
        'payload',
        'provider',
        'provider_subscriber_id',
        'provider_message_id',
        'provider_status',
        'status',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido()
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_id');
    }

    public function campanha()
    {
        return $this->belongsTo(Campanha::class, 'campanha_id');
    }

    /* =======================
       SCOPES DE FILTRO
       ======================= */

    public function scopeDoPeriodo(Builder $q, ?string $de, ?string $ate): Builder
    {
        if ($de) {
            $q->whereDate('sent_at', '>=', $de);
        }
        if ($ate) {
        // poderia usar whereDate, se você quiser incluir o dia todo
            $q->whereDate('sent_at', '<=', $ate);
        }
        return $q;
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        if ($status) {
            $q->where('status', $status);
        }
        return $q;
    }

    public function scopeTipo(Builder $q, ?string $tipo): Builder
    {
        if ($tipo) {
            $q->where('tipo', $tipo);
        }
        return $q;
    }

    public function scopeCanal(Builder $q, ?string $canal): Builder
    {
        if ($canal) {
            $q->where('canal', $canal);
        }
        return $q;
    }

    public function scopeDirecao(Builder $q, ?string $direcao): Builder
    {
        if ($direcao) {
            $q->where('direcao', $direcao);
        }
        return $q;
    }

    public function scopePorCliente(Builder $q, ?int $clienteId): Builder
    {
        if ($clienteId) {
            $q->where('cliente_id', $clienteId);
        }
        return $q;
    }

    public function scopePorPedido(Builder $q, ?int $pedidoId): Builder
    {
        if ($pedidoId) {
            $q->where('pedido_id', $pedidoId);
        }
        return $q;
    }

    public function scopePorCampanha(Builder $q, ?int $campanhaId): Builder
    {
        if ($campanhaId) {
            $q->where('campanha_id', $campanhaId);
        }
        return $q;
    }

    /**
     * Busca livre no conteúdo e no nome do cliente.
     */
    public function scopeBuscaLivre(Builder $q, ?string $busca): Builder
    {
        if ($busca) {
            $q->where(function (Builder $qq) use ($busca) {
                $qq->where('conteudo', 'like', '%' . $busca . '%')
                   ->orWhereHas('cliente', function (Builder $qc) use ($busca) {
                       $qc->where('nome', 'like', '%' . $busca . '%');
                   });
            });
        }
        return $q;
    }
}
