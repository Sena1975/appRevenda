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

            $telefone = $cliente->telefone ?? $cliente->phone ?? $cliente->whatsapp ?? null;


            if (!$telefone) {
                Log::warning('BotConversa: cliente criado sem telefone', [
                    'cliente_id' => $cliente->id,
                ]);
                return;
            }

            $clienteVindoDoApp = ($cliente->origem_cadastro ?? null) != 'app';

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

                    // opcional: marcar a tag de origem também para quem já existia
                    if ($originTagId) {
                        $botConversa->addTagToSubscriber($subscriberId, $originTagId);
                    }
                    // 🔹 Se veio do app, já manda boas-vindas
                    if ($clienteVindoDoApp) {
                        $mensagem = $this->mensagemBoasVindas($cliente);
                        $botConversa->sendMessageToSubscriber($subscriberId, $mensagem);
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

            // 🔹 Se veio do app, manda boas-vindas para quem acabou de ser criado
            if ($clienteVindoDoApp && $subscriberId) {
                $mensagem = $this->mensagemBoasVindas($cliente);
                $botConversa->sendMessageToSubscriber($subscriberId, $mensagem);
            }
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro ao integrar cliente novo', [
                'cliente_id' => $cliente->id,
                'erro'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Monta a mensagem de boas-vindas (ajuste o texto à vontade)
     */
    private function mensagemBoasVindas(Cliente $cliente): string
    {
        $nome = $cliente->nome ?: 'cliente';

        return "Olá {$nome}! 👋\n\n"
            . "Que bom ter você com a gente! 🎉\n"
            . "Seu cadastro no nosso app foi realizado com sucesso.\n\n"
            . "A partir de agora você vai receber por aqui atualizações importantes "
            . "sobre seus pedidos e novidades.\n\n"
            . "Se precisar de ajuda, é só responder esta mensagem. 🙂";
    }
}
