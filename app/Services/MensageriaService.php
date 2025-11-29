<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Mensagem;
use App\Models\PedidoVenda;
use App\Models\Campanha;
use App\Services\Whatsapp\BotConversaService;

class MensageriaService
{
    public function __construct(
        private BotConversaService $botConversa,
    ) {}

    /**
     * Envia uma mensagem WhatsApp via BotConversa
     * e registra tudo na tabela mensagens.
     *
     * $tipo é uma string pra você identificar o propósito:
     *   ex: 'recibo_entrega', 'indicacao_pendente', 'indicacao_premio_pix', 'boas_vindas_app' etc.
     */
    public function enviarWhatsapp(
        Cliente $cliente,
        string $conteudo,
        ?string $tipo = null,
        ?PedidoVenda $pedido = null,
        ?Campanha $campanha = null,
        array $payloadExtra = []
    ): Mensagem {
        // 1) registra a intenção no banco
        $mensagem = Mensagem::create([
            'cliente_id'  => $cliente->id,
            'pedido_id'   => $pedido?->id,
            'campanha_id' => $campanha?->id,
            'canal'       => 'whatsapp',
            'direcao'     => 'outbound',
            'tipo'        => $tipo,
            'conteudo'    => $conteudo,
            'status'      => 'queued',
            'provider'    => 'botconversa',
            'payload'     => $payloadExtra,
        ]);

        // 2) envia via BotConversa (usando o service que você já tem)
        $ok = $this->botConversa->enviarParaCliente($cliente, $conteudo);

        // 3) atualiza status interno
        $mensagem->status    = $ok ? 'sent' : 'failed';
        $mensagem->sent_at   = $ok ? now() : null;
        $mensagem->failed_at = $ok ? null : now();

        // Se no futuro você adaptar o BotConversaService para retornar
        // subscriber_id, message_id, provider_status, dá pra preencher aqui:
        // $mensagem->provider_subscriber_id = ...
        // $mensagem->provider_message_id    = ...
        // $mensagem->provider_status        = ...

        $mensagem->save();

        return $mensagem;
    }
}
