<?php

namespace App\Services\Whatsapp;

use App\Models\Cliente;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BotConversaService
{
    private Client $client;
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.botconversa.api_key');

        $this->client = new Client([
            'base_uri' => rtrim(config('services.botconversa.base_url'), '/') . '/',
            'timeout'  => 15,
            'http_errors' => false,
        ]);
    }

    /**
     * Normaliza telefone: mantém só dígitos e, se tiver 11 dígitos, prefixa 55.
     */
    private function normalizePhone(?string $telefoneBruto): ?string
    {
        if (!$telefoneBruto) {
            return null;
        }

        $telefone = preg_replace('/\D+/', '', $telefoneBruto);

        if (!$telefone) {
            return null;
        }

        // se vier 11 dígitos (DDD + número), coloca DDI 55
        if (strlen($telefone) === 11) {
            $telefone = '55' . $telefone;
        }

        return $telefone;
    }

    /**
     * Extrai o subscriber_id de um payload vindo da API.
     */
    public function getSubscriberIdFromPayload(array $data): ?string
    {
        if (isset($data['id'])) {
            return (string) $data['id'];
        }

        if (isset($data['subscriber_id'])) {
            return (string) $data['subscriber_id'];
        }

        if (isset($data['data']['id'])) {
            return (string) $data['data']['id'];
        }

        if (isset($data['data']['subscriber_id'])) {
            return (string) $data['data']['subscriber_id'];
        }

        return null;
    }

    /**
     * GET subscriber/get_by_phone/{phone}
     * Retorna array com dados do assinante ou null se não achar.
     */
    public function findSubscriberByPhone(string $telefoneBruto): ?array
    {
        if (!$this->apiKey) {
            Log::warning('BotConversa: API_KEY não configurada.');
            return null;
        }

        $phone = $this->normalizePhone($telefoneBruto);
        if (!$phone) {
            Log::warning('BotConversa: telefone inválido na busca', ['telefone' => $telefoneBruto]);
            return null;
        }

        try {
            $resp = $this->client->get("subscriber/get_by_phone/{$phone}/", [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
            ]);


            if ($resp->getStatusCode() === 400) {
                Log::info('BotConversa: get_by_phone 404 (assinante não encontrado)', [
                    'telefone' => $phone,
                ]);
                return null;
            }

            if ($resp->getStatusCode() !== 200) {
                Log::info('BotConversa: get_by_phone status != 200', [
                    'status' => $resp->getStatusCode(),
                    'telefone' => $phone,
                    'body'   => (string) $resp->getBody(),
                ]);
                return null;
            }

            $data = json_decode((string) $resp->getBody(), true) ?? [];

            if (isset($data['results'][0])) {
                return $data['results'][0];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em get_by_phone', [
                'erro'     => $e->getMessage(),
                'telefone' => $phone,
            ]);
            return null;
        }
    }

    /**
     * Cria o assinante (POST /subscriber/)
     */
    public function createSubscriber(string $telefoneBruto, ?string $nome = null): ?array
    {
        if (!$this->apiKey) {
            Log::warning('BotConversa: API_KEY não configurada.');
            return null;
        }

        $phone = $this->normalizePhone($telefoneBruto);
        if (!$phone) {
            Log::warning('BotConversa: telefone inválido na criação', ['telefone' => $telefoneBruto]);
            return null;
        }

        // Monta first_name e last_name a partir de $nome
        $firstName = 'Cliente';
        $lastName  = $phone;

        if ($nome) {
            $nome = trim($nome);
            $partes = preg_split('/\s+/', $nome, 2);

            $firstName = $partes[0] ?? 'Cliente';
            $lastName  = $partes[1] ?? $partes[0] ?? $phone;
        }

        try {
            $payload = [
                'phone'      => $phone,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'name'       => $nome ?? ($firstName . ' ' . $lastName),
            ];

            $resp = $this->client->post('subscriber/', [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
                'json' => $payload,
            ]);

            if (!in_array($resp->getStatusCode(), [200, 201])) {
                Log::warning('BotConversa: subscriber_create status inesperado', [
                    'status' => $resp->getStatusCode(),
                    'body'   => (string) $resp->getBody(),
                ]);
                return null;
            }

            $data = json_decode((string) $resp->getBody(), true) ?? [];

            // 🔹 NOVO: adiciona etiqueta de origem, se configurada
            $originTagId  = config('services.botconversa.origin_tag_id'); // vem do config/services.php
            $subscriberId = $this->getSubscriberIdFromPayload($data);

            if ($originTagId && $subscriberId) {
                $this->addTagToSubscriber($subscriberId, $originTagId);
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em subscriber_create', [
                'erro'     => $e->getMessage(),
                'telefone' => $phone,
            ]);
            return null;
        }
    }


    /**
     * Achar ou criar subscriber para um telefone.
     */
    public function getOrCreateSubscriber(string $telefoneBruto, ?string $nome = null): ?array
    {
        $subscriber = $this->findSubscriberByPhone($telefoneBruto);

        if (!$subscriber) {
            Log::info('BotConversa: assinante não encontrado, criando...', [
                'telefone' => $telefoneBruto,
            ]);

            $subscriber = $this->createSubscriber($telefoneBruto, $nome);
        }

        return $subscriber;
    }

    public function addTagToSubscriber(string $subscriberId, string|int $tagId): bool
    {
        if (!$this->apiKey) {
            Log::warning('BotConversa: API_KEY não configurada ao adicionar tag.');
            return false;
        }

        if (!$subscriberId || !$tagId) {
            return false;
        }

        try {
            $resp = $this->client->post("subscriber/{$subscriberId}/tags/{$tagId}/", [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
                // a API não exige body pra esse endpoint, então não mandamos json
            ]);

            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                Log::info('BotConversa: tag adicionada ao subscriber', [
                    'subscriber_id' => $subscriberId,
                    'tag_id'        => $tagId,
                    'status'        => $resp->getStatusCode(),
                ]);
                return true;
            }

            Log::warning('BotConversa: falha ao adicionar tag ao subscriber', [
                'subscriber_id' => $subscriberId,
                'tag_id'        => $tagId,
                'status'        => $resp->getStatusCode(),
                'body'          => (string) $resp->getBody(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em addTagToSubscriber', [
                'erro'          => $e->getMessage(),
                'subscriber_id' => $subscriberId,
                'tag_id'        => $tagId,
            ]);
            return false;
        }
    }


    /**
     * POST /subscriber/{subscriber_id}/send_message/
     */
    public function sendMessageToSubscriber(string $subscriberId, string $mensagem): bool
    {
        if (!$this->apiKey) {
            Log::warning('BotConversa: API_KEY não configurada.');
            return false;
        }

        if (!$subscriberId) {
            return false;
        }

        $payload = [
            'type'  => 'text',
            'value' => $mensagem,
        ];

        try {
            Log::info('BotConversa: enviando send_message', [
                'subscriber_id' => $subscriberId,
                'payload'       => $payload,
            ]);

            $resp = $this->client->post("subscriber/{$subscriberId}/send_message/", [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
                'json' => $payload,
            ]);

            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                Log::info('BotConversa: send_message OK', [
                    'subscriber_id' => $subscriberId,
                    'status'        => $resp->getStatusCode(),
                    'body'          => (string) $resp->getBody(),
                ]);
                return true;
            }

            Log::warning('BotConversa: send_message status inesperado', [
                'subscriber_id' => $subscriberId,
                'status'        => $resp->getStatusCode(),
                'body'          => (string) $resp->getBody(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em send_message', [
                'erro'          => $e->getMessage(),
                'subscriber_id' => $subscriberId,
                'payload'       => $payload,
            ]);
            return false;
        }
    }

    /**
     * Enviar mensagem usando apenas telefone/nome (fluxo antigo, mas agora centralizado).
     */
    public function enviarParaTelefone(string $telefoneBruto, string $mensagem, ?string $nome = null): bool
    {
        $subscriber = $this->getOrCreateSubscriber($telefoneBruto, $nome);

        if (!$subscriber) {
            Log::warning('BotConversa: não foi possível obter subscriber_id para envio', [
                'telefone' => $telefoneBruto,
            ]);
            return false;
        }

        $subscriberId = $this->getSubscriberIdFromPayload($subscriber);
        if (!$subscriberId) {
            Log::warning('BotConversa: payload sem subscriber_id', [
                'payload' => $subscriber,
            ]);
            return false;
        }

        return $this->sendMessageToSubscriber($subscriberId, $mensagem);
    }

    /**
     * Enviar mensagem diretamente para um Cliente (usando botconversa_subscriber_id se existir).
     */
    public function enviarParaCliente(Cliente $cliente, string $mensagem): bool
    {
        // Se já temos o subscriber_id salvo, usamos direto
        if (!empty($cliente->botconversa_subscriber_id)) {
            return $this->sendMessageToSubscriber($cliente->botconversa_subscriber_id, $mensagem);
        }

        // Senão, achar ou criar subscriber
        $telefone = $cliente->telefone ?? $cliente->phone ?? null;

        if (!$telefone) {
            Log::warning('BotConversa: Cliente sem telefone ao enviar mensagem', [
                'cliente_id' => $cliente->id,
            ]);
            return false;
        }

        $subscriber = $this->getOrCreateSubscriber($telefone, $cliente->nome);

        if (!$subscriber) {
            Log::warning('BotConversa: não foi possível obter subscriber ao enviar para cliente', [
                'cliente_id' => $cliente->id,
                'telefone'   => $telefone,
            ]);
            return false;
        }

        $subscriberId = $this->getSubscriberIdFromPayload($subscriber);
        if (!$subscriberId) {
            Log::warning('BotConversa: payload sem subscriber_id ao enviar para cliente', [
                'cliente_id' => $cliente->id,
                'payload'    => $subscriber,
            ]);
            return false;
        }

        // Salva o subscriber_id no cliente (sem disparar observers de novo)
        $cliente->botconversa_subscriber_id = $subscriberId;
        $cliente->saveQuietly();

        return $this->sendMessageToSubscriber($subscriberId, $mensagem);
    }
}
