<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Services\Whatsapp\BotConversaService;
use Illuminate\Support\Facades\Log;

class ClienteObserver
{
    public function created(Cliente $cliente): void
    {
        try {
            /** @var BotConversaService $botConversa */
            $botConversa = app(BotConversaService::class);

            $telefone = $cliente->telefone ?? $cliente->phone ?? null;

            if (!$telefone) {
                Log::warning('BotConversa: cliente criado sem telefone', [
                    'cliente_id' => $cliente->id,
                ]);
                return;
            }

            $subscriber = $botConversa->getOrCreateSubscriber($telefone, $cliente->nome);

            if (!$subscriber) {
                Log::warning('BotConversa: falha ao criar/obter subscriber ao cadastrar cliente', [
                    'cliente_id' => $cliente->id,
                    'telefone'   => $telefone,
                ]);
                return;
            }

            $subscriberId = $botConversa->getSubscriberIdFromPayload($subscriber);

            if ($subscriberId) {
                $cliente->botconversa_subscriber_id = $subscriberId;
                // saveQuietly para não disparar o observer de novo
                $cliente->saveQuietly();
            }

            Log::info('BotConversa: subscriber sincronizado ao cadastrar cliente', [
                'cliente_id'    => $cliente->id,
                'telefone'      => $telefone,
                'subscriber_id' => $subscriberId,
            ]);

            // OPCIONAL: mensagem de boas-vindas
            /*
            if ($subscriberId) {
                $botConversa->sendMessageToSubscriber(
                    $subscriberId,
                    "Olá {$cliente->nome}! 👋\n\nSeu cadastro foi realizado com sucesso!"
                );
            }
            */

        } catch (\Throwable $e) {
            Log::error('BotConversa: erro ao integrar cliente novo', [
                'cliente_id' => $cliente->id,
                'erro'       => $e->getMessage(),
            ]);
        }
    }
}
