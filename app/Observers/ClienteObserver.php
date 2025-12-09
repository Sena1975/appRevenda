<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\WhatsappConfig;
use App\Services\Whatsapp\BotConversaService;
use Illuminate\Support\Facades\Log;

class ClienteObserver
{
    public function created(Cliente $cliente): void
    {
        try {
            /** @var BotConversaService $botConversa */
            $botConversa = app(BotConversaService::class);

            // empresa do cliente (multi-empresa)
            $empresaId = $cliente->empresa_id ?? null;

            $telefone = $cliente->telefone
                ?? $cliente->phone
                ?? $cliente->whatsapp
                ?? null;

            if (!$telefone) {
                Log::warning('BotConversa: cliente criado sem telefone', [
                    'cliente_id' => $cliente->id,
                ]);
                return;
            }

            // Se origem_cadastro != 'app', consideramos "vindo do app" (como você já fazia)
            $clienteVindoDoApp = ($cliente->origem_cadastro ?? null) != 'app';

            /**
             * ORIGIN TAG:
             * 1) Tenta pegar da WhatsappConfig (origin_tag_id) da empresa
             * 2) Se não tiver, mantém fallback no .env antigo (se ainda estiver configurado)
             */
            $originTagId = null;

            if ($empresaId) {
                $originTagId = WhatsappConfig::where('empresa_id', $empresaId)
                    ->where('provider', 'botconversa')
                    ->where('ativo', 1)
                    ->orderByDesc('is_default')
                    ->value('origin_tag_id');
            }

            if (!$originTagId) {
                // fallback legado (se ainda existir no config)
                $originTagId = config('services.botconversa.origin_tag_id');
            }

            /**
             * 1) Tenta achar o contato no BotConversa
             */
            $subscriber = $botConversa->findSubscriberByPhone($telefone, $empresaId);

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
                        'empresa_id'    => $empresaId,
                    ]);

                    // opcional: marcar a tag de origem também para quem já existia
                    if ($originTagId) {
                        $botConversa->addTagToSubscriber($subscriberId, $originTagId, $empresaId);
                    }

                    // 🔹 Se veio do app, já manda boas-vindas
                    if ($clienteVindoDoApp) {
                        $mensagem = $this->mensagemBoasVindas($cliente);
                        $botConversa->sendMessageToSubscriber($subscriberId, $mensagem, $empresaId);
                    }
                }

                return;
            }

            /**
             * 2) Se não existir, cria (e o createSubscriber já adiciona a etiqueta
             *    usando o origin_tag_id configurado na WhatsappConfig)
             */
            Log::info('BotConversa: assinante não encontrado ao criar cliente, criando no BotConversa...', [
                'cliente_id' => $cliente->id,
                'telefone'   => $telefone,
                'empresa_id' => $empresaId,
            ]);

            $subscriber = $botConversa->createSubscriber($telefone, $cliente->nome, $empresaId);

            if (!$subscriber) {
                Log::warning('BotConversa: falha ao criar subscriber ao cadastrar cliente', [
                    'cliente_id' => $cliente->id,
                    'telefone'   => $telefone,
                    'empresa_id' => $empresaId,
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
                'empresa_id'    => $empresaId,
            ]);

            // 🔹 Se veio do app, manda boas-vindas para quem acabou de ser criado
            if ($clienteVindoDoApp && $subscriberId) {
                $mensagem = $this->mensagemBoasVindas($cliente);
                $botConversa->sendMessageToSubscriber($subscriberId, $mensagem, $empresaId);
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
