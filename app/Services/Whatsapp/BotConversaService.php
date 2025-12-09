<?php

namespace App\Services\Whatsapp;

use App\Models\Cliente;
use App\Models\WhatsappConfig;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BotConversaService
{
    /**
     * Client HTTP da instância atual.
     */
    private ?Client $client = null;

    /**
     * API-KEY usada nos headers.
     */
    private ?string $apiKey = null;

    /**
     * Configuração carregada para a empresa atual.
     */
    private ?WhatsappConfig $config = null;

    /**
     * empresa_id atualmente associada a este client/config.
     */
    private ?int $empresaIdAtual = null;

    public function __construct()
    {
        // Agora o client é construído sob demanda (lazy),
        // baseado na WhatsappConfig da empresa.
    }

    /**
     * Resolve o empresa_id a partir de:
     *  - parâmetro explícito
     *  - usuário logado (Auth::user()->empresa_id)
     */
    protected function resolveEmpresaId(?int $empresaId = null): ?int
    {
        if ($empresaId) {
            return $empresaId;
        }

        $user = Auth::user();

        return $user?->empresa_id;
    }

    /**
     * Busca a configuração de WhatsApp (provider = botconversa) da empresa.
     * Sempre tenta pegar uma conexão ativa, priorizando a marcada como padrão.
     */
    protected function getConfig(?int $empresaId = null): ?WhatsappConfig
    {
        $empresaId = $this->resolveEmpresaId($empresaId);

        if (!$empresaId) {
            Log::warning('BotConversa: empresa_id não definido ao buscar configuração.');
            return null;
        }

        // Se já temos em cache e é da mesma empresa, reaproveita
        if ($this->config && $this->empresaIdAtual === $empresaId) {
            return $this->config;
        }

        $config = WhatsappConfig::where('empresa_id', $empresaId)
            ->where('provider', 'botconversa')
            ->where('ativo', 1)
            ->orderByDesc('is_default') // padrão primeiro
            ->orderByDesc('id')
            ->first();

        if (!$config) {
            Log::warning('BotConversa: nenhuma WhatsappConfig BotConversa ativa encontrada para a empresa.', [
                'empresa_id' => $empresaId,
            ]);
            return null;
        }

        $this->config        = $config;
        $this->empresaIdAtual = $empresaId;
        $this->apiKey        = $config->api_key; // campo da tabela whatsapp_configs (ajuste se o nome for outro)

        // Zera o client pra forçar recriação com base_uri correta
        $this->client = null;

        return $config;
    }

    /**
     * Retorna (ou cria) o Client Guzzle configurado para a empresa.
     */
    protected function getClient(?int $empresaId = null): ?Client
    {
        $config = $this->getConfig($empresaId);

        if (!$config) {
            return null;
        }

        if (!$config->api_url || !$config->api_key) {
            Log::warning('BotConversa: configuração da empresa sem api_url ou api_key.', [
                'empresa_id' => $config->empresa_id,
            ]);
            return null;
        }

        if ($this->client && $this->empresaIdAtual === $config->empresa_id) {
            return $this->client;
        }

        $this->apiKey = $config->api_key;

        $this->client = new Client([
            'base_uri'    => rtrim($config->api_url, '/') . '/',
            'timeout'     => 15,
            'http_errors' => false,
        ]);

        return $this->client;
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
    public function findSubscriberByPhone(string $telefoneBruto, ?int $empresaId = null): ?array
    {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
            Log::warning('BotConversa: API_KEY ou client não configurados em findSubscriberByPhone.');
            return null;
        }

        $phone = $this->normalizePhone($telefoneBruto);
        if (!$phone) {
            Log::warning('BotConversa: telefone inválido na busca', ['telefone' => $telefoneBruto]);
            return null;
        }

        try {
            $resp = $client->get("subscriber/get_by_phone/{$phone}/", [
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
                    'status'   => $resp->getStatusCode(),
                    'telefone' => $phone,
                    'body'     => (string) $resp->getBody(),
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
    public function createSubscriber(
        string $telefoneBruto,
        ?string $nome = null,
        ?int $empresaId = null
    ): ?array {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
            Log::warning('BotConversa: API_KEY ou client não configurados em createSubscriber.');
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

            $resp = $client->post('subscriber/', [
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

            // 🔹 Tag de origem a partir da configuração da empresa (se existir),
            // com fallback para o config antigo.
            $config = $this->config ?? $this->getConfig($empresaId);

            $originTagIdConfig = $config->origin_tag_id ?? null;
            $originTagIdEnv    = config('services.botconversa.origin_tag_id');
            $originTagId       = $originTagIdConfig ?: $originTagIdEnv;

            $subscriberId = $this->getSubscriberIdFromPayload($data);

            if ($originTagId && $subscriberId) {
                $this->addTagToSubscriber($subscriberId, $originTagId, $empresaId);
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
    public function getOrCreateSubscriber(
        string $telefoneBruto,
        ?string $nome = null,
        ?int $empresaId = null
    ): ?array {
        $subscriber = $this->findSubscriberByPhone($telefoneBruto, $empresaId);

        if (!$subscriber) {
            Log::info('BotConversa: assinante não encontrado, criando...', [
                'telefone'   => $telefoneBruto,
                'empresa_id' => $this->resolveEmpresaId($empresaId),
            ]);

            $subscriber = $this->createSubscriber($telefoneBruto, $nome, $empresaId);
        }

        return $subscriber;
    }

    public function addTagToSubscriber(
        string $subscriberId,
        string|int $tagId,
        ?int $empresaId = null
    ): bool {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
            Log::warning('BotConversa: API_KEY ou client não configurados ao adicionar tag.');
            return false;
        }

        if (!$subscriberId || !$tagId) {
            return false;
        }

        try {
            $resp = $client->post("subscriber/{$subscriberId}/tags/{$tagId}/", [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
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
    public function sendMessageToSubscriber(
        string $subscriberId,
        string $mensagem,
        ?int $empresaId = null
    ): bool {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
            Log::warning('BotConversa: API_KEY ou client não configurados em sendMessageToSubscriber.');
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

            $resp = $client->post("subscriber/{$subscriberId}/send_message/", [
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
     * Enviar mensagem usando apenas telefone/nome.
     * Agora multi-empresa (usa empresa do usuário logado, salvo se você passar empresaId).
     */
    public function enviarParaTelefone(
        string $telefoneBruto,
        string $mensagem,
        ?string $nome = null,
        ?int $empresaId = null
    ): bool {
        $subscriber = $this->getOrCreateSubscriber($telefoneBruto, $nome, $empresaId);

        if (!$subscriber) {
            Log::warning('BotConversa: não foi possível obter subscriber_id para envio', [
                'telefone'   => $telefoneBruto,
                'empresa_id' => $this->resolveEmpresaId($empresaId),
            ]);
            return false;
        }

        $subscriberId = $this->getSubscriberIdFromPayload($subscriber);
        if (!$subscriberId) {
            Log::warning('BotConversa: payload sem subscriber_id', [
                'payload'    => $subscriber,
                'empresa_id' => $this->resolveEmpresaId($empresaId),
            ]);
            return false;
        }

        return $this->sendMessageToSubscriber($subscriberId, $mensagem, $empresaId);
    }

    /**
     * Enviar mensagem diretamente para um Cliente (usando botconversa_subscriber_id se existir).
     */
    public function enviarParaCliente(
        Cliente $cliente,
        string $mensagem,
        ?int $empresaId = null
    ): bool {
        // Se empresaId não foi informado, tenta usar do próprio cliente
        $empresaId = $empresaId ?? ($cliente->empresa_id ?? null);

        // Se já temos o subscriber_id salvo, usamos direto
        if (!empty($cliente->botconversa_subscriber_id)) {
            return $this->sendMessageToSubscriber(
                $cliente->botconversa_subscriber_id,
                $mensagem,
                $empresaId
            );
        }

        // Senão, achar ou criar subscriber
        $telefone = $cliente->telefone ?? $cliente->phone ?? $cliente->whatsapp ?? null;

        if (!$telefone) {
            Log::warning('BotConversa: Cliente sem telefone ao enviar mensagem', [
                'cliente_id' => $cliente->id,
            ]);
            return false;
        }

        $subscriber = $this->getOrCreateSubscriber($telefone, $cliente->nome, $empresaId);

        if (!$subscriber) {
            Log::warning('BotConversa: não foi possível obter subscriber ao enviar para cliente', [
                'cliente_id' => $cliente->id,
                'telefone'   => $telefone,
                'empresa_id' => $this->resolveEmpresaId($empresaId),
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

        return $this->sendMessageToSubscriber($subscriberId, $mensagem, $empresaId);
    }
}
