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
     * Envia uma mensagem WhatsApp via BotConversa e registra na tabela appmensagens.
     *
     * $tipo: string para identificar o propósito:
     *   ex: 'recibo_entrega', 'indicacao_pendente', 'indicacao_premio_pix', 'boas_vindas_app' etc.
     *
     * Regras de dedupe (anti-duplicidade):
     * - Se já existir mensagem com mesmo cliente + tipo (+ pedido_id/campanha_id quando existirem)
     *   e status in (queued, sent), então NÃO envia novamente e retorna a mensagem existente.
     * - Se existir failed, por padrão tenta reenviar (reenviarSeFalhou=true).
     * - Se force=true, sempre envia (ignora dedupe).
     */
    public function enviarWhatsapp(
        Cliente $cliente,
        string $conteudo,
        ?string $tipo = null,
        ?PedidoVenda $pedido = null,
        ?Campanha $campanha = null,
        array $payloadExtra = [],
        bool $force = false,
        bool $reenviarSeFalhou = true
    ): Mensagem {

        // =========================
        // 0) Dedupe (anti-duplicidade)
        // =========================
        if (!$force && $tipo) {
            $dedupeQuery = Mensagem::query()
                ->where('cliente_id', $cliente->id)
                ->where('canal', 'whatsapp')
                ->where('direcao', 'outbound')
                ->where('tipo', $tipo);

            if ($pedido?->id) {
                $dedupeQuery->where('pedido_id', $pedido->id);
            }

            if ($campanha?->id) {
                $dedupeQuery->where('campanha_id', $campanha->id);
            }

            $existente = $dedupeQuery->orderByDesc('id')->first();

            if ($existente) {
                // Se já foi enviada ou está na fila, não duplica
                if (in_array($existente->status, ['queued', 'sent'], true)) {
                    return $existente;
                }

                // Se falhou e não queremos reenviar, retorna ela
                if ($existente->status === 'failed' && !$reenviarSeFalhou) {
                    return $existente;
                }
            }
        }

        // Coloca contexto mínimo no payload (ajuda debug e auditoria)
        $payloadExtra = array_merge([
            'empresa_id' => $cliente->empresa_id ?? null,
        ], $payloadExtra);

        // =========================
        // 1) registra a intenção no banco
        // =========================
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

        // =========================
        // 2) envia via BotConversa
        // =========================
        $empresaId = $cliente->empresa_id ?? null;
        $ok = $this->botConversa->enviarParaCliente($cliente, $conteudo, $empresaId);

        // =========================
        // 3) atualiza status interno + metadados do provider
        // =========================
        $mensagem->status    = $ok ? 'sent' : 'failed';
        $mensagem->sent_at   = $ok ? now() : null;
        $mensagem->failed_at = $ok ? null : now();

        // Se o BotConversaService gravou subscriber_id no cliente, salva também na mensagem
        $mensagem->provider_subscriber_id = $cliente->botconversa_subscriber_id ?: null;
        $mensagem->provider_status        = $ok ? 'sent' : 'failed';

        $mensagem->save();

        return $mensagem;
    }
}
