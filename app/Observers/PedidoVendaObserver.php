<?php

namespace App\Observers;

use App\Models\PedidoVenda;
use App\Services\Whatsapp\MensagensCampanhaService;
use App\Services\Whatsapp\BotConversaService;
use App\Services\CampanhaService;
use Illuminate\Support\Facades\Log;

class PedidoVendaObserver
{
    public function created(PedidoVenda $pedido): void
    {
        try {
            // 1) CAMPANHA DE INDICAÇÃO: APENAS AVISO 1 (pedido pendente) → vai para o INDICADOR
            if ($this->deveDispararIndicacao($pedido) && $pedido->status === 'PENDENTE') {

                $indicador = $pedido->indicador;
                $indicado  = $pedido->cliente;

                if ($indicador && $indicado) {
                    /** @var MensagensCampanhaService $mensagens */
                    $mensagens = app(MensagensCampanhaService::class);

                    $ok = $mensagens->enviarAvisoIndicadorPedidoPendente($indicador, $indicado, $pedido);

                    Log::info('Campanha indicação: msg PENDENTE enviada ao indicador', [
                        'pedido_id'    => $pedido->id,
                        'indicador_id' => $indicador->id,
                        'resultado'    => $ok,
                    ]);
                }
            }

            // Nenhum recibo de entrega é enviado aqui (somente aviso para INDICADOR).

        } catch (\Throwable $e) {
            Log::error('PedidoVendaObserver@created erro', [
                'pedido_id' => $pedido->id,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    public function updated(PedidoVenda $pedido): void
    {
        try {
            // só reage se o status mudou
            if (!$pedido->wasChanged('status')) {
                return;
            }

            // quando mudou para ENTREGUE
            if ($pedido->status === 'ENTREGUE') {

                $cliente   = $pedido->cliente;
                $indicador = $pedido->indicador;

                /** @var BotConversaService $botConversa */
                $botConversa = app(BotConversaService::class);

                // 2.a) 🚫 NÃO enviamos mais recibo para o CLIENTE aqui.
                //      O recibo/parabéns é enviado pelo PedidoVendaController::confirmarEntrega()
                //      via $this->enviarReciboWhatsApp($pedido).

                // 2.b) CAMPANHA DE INDICAÇÃO → PRÊMIO + PIX → INDICADOR
                if ($this->deveDispararIndicacao($pedido) && $indicador && $cliente) {

                    /** @var MensagensCampanhaService $mensagens */
                    $mensagens = app(MensagensCampanhaService::class);

                    $okIndicador = $mensagens
                        ->enviarAvisoIndicadorPremioDisponivel($indicador, $cliente, $pedido);

                    Log::info('Campanha indicação: msg PRÊMIO enviada ao indicador', [
                        'pedido_id'    => $pedido->id,
                        'indicador_id' => $indicador->id,
                        'resultado'    => $okIndicador,
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('PedidoVendaObserver@updated erro', [
                'pedido_id' => $pedido->id,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Regra dinâmica usando CampanhaService + metodo_php.
     */
    private function deveDispararIndicacao(PedidoVenda $pedido): bool
    {
        // precisa ter indicador vinculado
        if (!$pedido->indicador_id) {
            return false;
        }

        // precisa ter campanha vinculada
        if (!$pedido->campanha_id) {
            return false;
        }

        /** @var CampanhaService $campanhaService */
        $campanhaService = app(CampanhaService::class);

        // 👉 aqui usamos exatamente o valor que você me passou:
        // appcampanha.metodo_php = "isCampanhaIndicacao"
        $metodoPhpIndicacao = 'isCampanhaIndicacao';

        $campanhasIndicacaoIds = $campanhaService
            ->campanhasVigentesIdsPorMetodo($metodoPhpIndicacao);

        Log::info('deveDispararIndicacao check', [
            'pedido_id'             => $pedido->id,
            'pedido_campanha_id'    => $pedido->campanha_id,
            'campanhas_indicacao'   => $campanhasIndicacaoIds,
        ]);

        if (empty($campanhasIndicacaoIds)) {
            return false;
        }

        return in_array((int) $pedido->campanha_id, $campanhasIndicacaoIds, true);
    }
}
