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

            $originTagId = config('services.botconversa.origin_tag_id');

            /**
             * 1) Tenta achar o contato no BotConversa
             */
            $subscriber = $botConversa->findSubscriberByPhone($telefone);

            if ($subscriber) {
                $subscriberId = $botConversa->getSubscriberIdFromPayload($subscriber);

                if ($subscriberId) {
                    // sincroniza o subscriber_id no cliente
                    $cliente->botconversa_subscriber_id = $subscriberId;
                    $cliente->saveQuietly();

                    Log::info('BotConversa: subscriber existente vinculado ao cliente', [
                        'cliente_id'    => $cliente->id,
                        'telefone'      => $telefone,
                        'subscriber_id' => $subscriberId,
                    ]);

                    // OPCIONAL: se você quiser também marcar a tag de origem
                    // mesmo para contatos que já existiam no BotConversa:
                    if ($originTagId) {
                        $botConversa->addTagToSubscriber($subscriberId, $originTagId);
                    }
                }

                return;
            }

            /**
             * 2) Se não existir, cria (e o createSubscriber já adiciona a etiqueta)
             */
            Log::info('BotConversa: assinante não encontrado ao criar cliente, criando no BotConversa...', [
                'cliente_id' => $cliente->id,
                'telefone'   => $telefone,
            ]);

            $subscriber = $botConversa->createSubscriber($telefone, $cliente->nome);

            if (!$subscriber) {
                Log::warning('BotConversa: falha ao criar subscriber ao cadastrar cliente', [
                    'cliente_id' => $cliente->id,
                    'telefone'   => $telefone,
                ]);
                return;
            }

            $subscriberId = $botConversa->getSubscriberIdFromPayload($subscriber);

            if ($subscriberId) {
                $cliente->botconversa_subscriber_id = $subscriberId;
                $cliente->saveQuietly();
            }

            Log::info('BotConversa: subscriber criado e vinculado ao cliente', [
                'cliente_id'    => $cliente->id,
                'telefone'      => $telefone,
                'subscriber_id' => $subscriberId,
            ]);

        } catch (\Throwable $e) {
            Log::error('BotConversa: erro ao integrar cliente novo', [
                'cliente_id' => $cliente->id,
                'erro'       => $e->getMessage(),
            ]);
        }
    }
}
