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
     * API Key atual (cacheada) para os headers.
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
        // Client é construído sob demanda (lazy), baseado na WhatsappConfig da empresa.
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
            Log::warning('BotConversa: nenhuma configuração ativa encontrada.', [
                'empresa_id' => $empresaId,
            ]);
            return null;
        }

        $this->config        = $config;
        $this->empresaIdAtual = $empresaId;
        $this->apiKey        = $this->sanitizeApiKey($config->api_key); // remove aspas/espacos acidentais

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

        $this->apiKey = $this->sanitizeApiKey($config->api_key);

        $baseUrl = $this->sanitizeBaseUrl($config->api_url);

        if ($baseUrl === '' || $this->apiKey === '') {
            Log::warning('BotConversa: baseUrl/apiKey inválidos após sanitização.', [
                'empresa_id' => $config->empresa_id,
                'baseUrl'    => $baseUrl,
            ]);
            return null;
        }

        $this->client = new Client([
            'base_uri'    => $baseUrl . '/',
            'timeout'     => 15,
            'http_errors' => false,
        ]);

        return $this->client;
    }

    /**
     * Remove espaços e aspas acidentais em valores vindos do banco.
     */
    private function sanitizeScalar(?string $value): string
    {
        $v = trim((string) $value);
        // remove aspas no começo/fim (ex: '"abc"' ou "'abc'")
        $v = trim($v, "\"'");
        return $v;
    }

    /**
     * Normaliza a base URL do BotConversa.
     * - remove aspas/espacos
     * - remove barra final
     * - garante sufixo /webhook (necessário para os endpoints usados aqui)
     */
    private function sanitizeBaseUrl(?string $url): string
    {
        $base = $this->sanitizeScalar($url);
        $base = rtrim($base, '/');

        if ($base !== '' && !str_ends_with($base, '/webhook')) {
            $base .= '/webhook';
        }

        return $base;
    }

    /**
     * Normaliza a API key (remove aspas/espacos).
     */
    private function sanitizeApiKey(?string $key): string
    {
        return $this->sanitizeScalar($key);
    }

    /**
     * Normaliza telefone: mantém só dígitos e, se tiver 11 dígitos, prefixa 55.
     */
    protected function normalizePhone(?string $telefone): ?string
    {
        if (!$telefone) {
            return null;
        }

        // mantém só dígitos
        $telefone = preg_replace('/\D+/', '', $telefone);

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
        // Alguns endpoints podem retornar:
        //  - ['id' => 123]
        //  - ['subscriber' => ['id' => 123]]
        //  - ['results' => [['id' => 123]]]
        if (isset($data['id'])) {
            return (string) $data['id'];
        }

        if (isset($data['subscriber']['id'])) {
            return (string) $data['subscriber']['id'];
        }

        if (isset($data['results'][0]['id'])) {
            return (string) $data['results'][0]['id'];
        }

        return null;
    }

    /**
     * Busca assinante pelo telefone (GET /subscriber/get_by_phone/{phone}/)
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

            // Alguns cenários o BotConversa retorna 400 quando não encontra (mantive seu comportamento)
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
            Log::warning('BotConversa: telefone inválido para criar subscriber', [
                'telefone' => $telefoneBruto,
            ]);
            return null;
        }

        $nome = trim((string) $nome);
        $firstName = $nome ? explode(' ', $nome)[0] : 'Cliente';
        $lastName  = $nome ? trim(substr($nome, strlen($firstName))) : '';

        try {
            $payload = [
                'phone'      => $phone,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'name'       => $nome ?: ($firstName . ' ' . $lastName),
            ];

            $resp = $client->post('subscriber/', [
                'headers' => [
                    'Accept'  => 'application/json',
                    'API-KEY' => $this->apiKey,
                ],
                'json' => $payload,
            ]);

            if (!in_array($resp->getStatusCode(), [200, 201])) {
                Log::warning('BotConversa: create subscriber status inesperado', [
                    'status' => $resp->getStatusCode(),
                    'body'   => (string) $resp->getBody(),
                ]);
                return null;
            }

            $data = json_decode((string) $resp->getBody(), true) ?? [];
            return $data;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em create subscriber', [
                'erro'     => $e->getMessage(),
                'telefone' => $phone,
            ]);
            return null;
        }
    }

    /**
     * Busca ou cria o subscriber pelo telefone.
     */
    public function getOrCreateSubscriber(
        string $telefoneBruto,
        ?string $nome = null,
        ?int $empresaId = null
    ): ?array {
        $subscriber = $this->findSubscriberByPhone($telefoneBruto, $empresaId);

        if ($subscriber) {
            return $subscriber;
        }

        Log::info('BotConversa: assinante não encontrado, criando...', [
            'telefone'   => $telefoneBruto,
            'empresa_id' => $this->resolveEmpresaId($empresaId),
        ]);

        return $this->createSubscriber($telefoneBruto, $nome, $empresaId);
    }

    /**
     * Adiciona uma tag no subscriber (POST /subscriber/{id}/tags/{tagId}/)
     */
    public function addTagToSubscriber(string $subscriberId, string $tagId, ?int $empresaId = null): bool
    {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
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

            if ($resp->getStatusCode() !== 200) {
                Log::warning('BotConversa: addTag status inesperado', [
                    'subscriber_id' => $subscriberId,
                    'tag_id'        => $tagId,
                    'status'        => $resp->getStatusCode(),
                    'body'          => (string) $resp->getBody(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('BotConversa: erro em addTag', [
                'erro'          => $e->getMessage(),
                'subscriber_id' => $subscriberId,
                'tag_id'        => $tagId,
            ]);
            return false;
        }
    }

    /**
     * Envia uma mensagem (POST /subscriber/{id}/send_message/)
     */
    public function sendMessageToSubscriber(string $subscriberId, string $mensagem, ?int $empresaId = null): bool
    {
        $client = $this->getClient($empresaId);

        if (!$client || !$this->apiKey) {
            return false;
        }

        if (!$subscriberId || !$mensagem) {
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

            if (!in_array($resp->getStatusCode(), [200, 201])) {
                Log::warning('BotConversa: send_message status inesperado', [
                    'subscriber_id' => $subscriberId,
                    'status'        => $resp->getStatusCode(),
                    'body'          => (string) $resp->getBody(),
                ]);
                return false;
            }

            return true;
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
     * Envia mensagem diretamente para telefone (resolve/gera subscriber).
     */
    public function enviarParaTelefone(string $telefone, string $mensagem, ?int $empresaId = null, ?string $nome = null): bool
    {
        $subscriber = $this->getOrCreateSubscriber($telefone, $nome, $empresaId);

        if (!$subscriber) {
            return false;
        }

        $subscriberId = $this->getSubscriberIdFromPayload($subscriber);

        if (!$subscriberId) {
            Log::warning('BotConversa: payload sem subscriber_id ao enviarParaTelefone', [
                'telefone' => $telefone,
                'payload'  => $subscriber,
            ]);
            return false;
        }

        return $this->sendMessageToSubscriber($subscriberId, $mensagem, $empresaId);
    }

    /**
     * Envia mensagem para um Cliente:
     * - Usa botconversa_subscriber_id se existir
     * - Senão tenta achar/criar pelo telefone
     * - Salva subscriber_id no cliente (quietly)
     */
    public function enviarParaCliente(Cliente $cliente, string $mensagem, ?int $empresaId = null): bool
    {
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
            Log::warning('BotConversa: cliente sem telefone para envio', [
                'cliente_id' => $cliente->id,
            ]);
            return false;
        }

        $subscriber = $this->getOrCreateSubscriber($telefone, $cliente->nome ?? null, $empresaId);

        if (!$subscriber) {
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
