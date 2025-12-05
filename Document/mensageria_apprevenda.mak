Integração WhatsApp + BotConversa + Campanhas
Visão geral

O sistema integra:

Pedido de venda (app de revenda)

BotConversa (envio de mensagens WhatsApp)

Camadas internas de mensageria, campanhas e indicação

Objetivos principais:

Cadastrar automaticamente clientes no BotConversa ao criar clientes no sistema.

Sincronizar envios de WhatsApp com o BotConversa (assinantes, tags, mensagens).

Registrar tudo em banco (appmensagens) para relatórios (por cliente, pedido, campanha, status, tipo etc.).

Automatizar campanhas de indicação:

Mensagem para o indicador ao registrar a primeira compra do indicado.

Mensagem para o cliente ao registrar o pedido.

Mensagem para o indicador ao entregar o pedido (prêmio/PIX).

Recibo de entrega para o cliente.

Configuração da integração BotConversa

Arquivo de configuração: config/services.php

Trecho relevante:

'botconversa' => [
    'base_url'   => env('BOTCONVERSA_BASE_URL', 'https://app.botconversa.com.br/api/v1/'),
    'api_key'    => env('BOTCONVERSA_API_KEY'),
    'origin_tag_id' => env('BOTCONVERSA_ORIGIN_TAG_ID'), // Tag opcional p/ origem "Sistema de Revenda"
],

Variáveis .env

BOTCONVERSA_BASE_URL="https://app.botconversa.com.br/api/v1/"
BOTCONVERSA_API_KEY="SUA_API_KEY_AQUI"
BOTCONVERSA_ORIGIN_TAG_ID=123456   # opcional (tag de origem)

Importante:

Sempre defina a API Key correta, fornecida pelo BotConversa.

Opcionalmente, configure uma TAG para identificar contatos vindos do sistema de revenda.

Essa tag pode ser usada depois para segmentar campanhas diretamente no BotConversa (ex.: “Origem: appRevenda”).

Tabela de log de mensageria (appmensagens)

Nome da tabela: appmensagens

Objetivo:

Registrar tudo que é enviado/recebido em termos de WhatsApp/Mensageria, amarrando sempre que possível ao:

Cliente (cliente_id)

Pedido (pedido_id)

Campanha (campanha_id)

Campos sugeridos (já implementados)

id (bigint, PK, auto incremento)

cliente_id (nullable, FK para appcliente)

pedido_id (nullable, FK para apppedidovenda)

campanha_id (nullable, FK para appcampanha)

canal (string) — Ex.: "whatsapp", "sms", "email" (no momento usamos whatsapp)

direcao (string) — "outbound" (saindo do sistema) ou "inbound" (chegando no sistema).

tipo (string) — Tipo lógico da mensagem (ex.: "boas_vindas", "confirmacao_pedido", "campanha_indicacao_aviso_indicador", etc.)

conteudo (text) — Corpo textual da mensagem enviada/recebida.

payload (json, nullable) — Guarda o payload bruto enviado/recebido do provedor (BotConversa), quando desejado.

provider (string, nullable) — Ex.: "botconversa" (no futuro pode existir "z-api", "twilio" etc).

provider_subscriber_id (string, nullable) — ID do contato/assinante no provedor externo.

provider_message_id (string, nullable) — ID da mensagem no provedor.

provider_status (string, nullable) — Status retornado pelo provedor (ex.: "queued", "sent", "delivered", "failed", "read").

status (string) — Status lógico interno: "queued", "sent", "delivered", "failed".

sent_at (datetime, nullable) — Data/hora em que o sistema marcou como enviada.

delivered_at (datetime, nullable) — Data/hora de entrega (se controlado).

failed_at (datetime, nullable) — Data/hora de falha.

created_at / updated_at — padrão Laravel.

Modelo Mensagem (log de mensageria)

Arquivo: app/Models/Mensagem.php
Tabela: appmensagens

Campos principais
protected $fillable = [
    'cliente_id',
    'pedido_id',
    'campanha_id',
    'canal',      // ex.: 'whatsapp'
    'direcao',    // 'outbound' (enviado) ou 'inbound' (recebido)
    'tipo',       // tipo lógico da mensagem (chave interna)
    'conteudo',
    'payload',    // array extra (JSON)
    'provider',   // ex.: 'botconversa'
    'provider_subscriber_id',
    'provider_message_id',
    'provider_status',
    'status',     // 'queued','sent','failed'
    'sent_at',
    'delivered_at',
    'failed_at',
];

Casts
protected $casts = [
    'payload'      => 'array',
    'sent_at'      => 'datetime',
    'delivered_at' => 'datetime',
    'failed_at'    => 'datetime',
];

Relacionamentos típicos
public function cliente()
{
    return $this->belongsTo(Cliente::class, 'cliente_id');
}

public function pedido()
{
    return $this->belongsTo(PedidoVenda::class, 'pedido_id');
}

public function campanha()
{
    return $this->belongsTo(Campanha::class, 'campanha_id');
}

Serviço de integração com BotConversa

Arquivo: app/Services/Whatsapp/BotConversaService.php

Objetivo:

Encapsular toda lógica de comunicação com API do BotConversa.

Responsabilidades:

Montar requisições HTTP autenticadas.

Buscar assinante por telefone.

Criar assinante (subscriber) se não existir.

Enviar mensagem para assinante.

Funções principais

Construtor

public function __construct(Client $http, ?string $apiKey = null, ?string $baseUrl = null)

Recebe:

instância de GuzzleHttp\Client (via DI do Laravel),

apiKey (lida do config/services.php caso null),

baseUrl (também do config/services.php).

Busca as configs em:

config('services.botconversa.api_key');
config('services.botconversa.base_url');

Se não houver apiKey configurada:

Lança log de erro.

Pode lançar exception customizada ou simplesmente não enviar.

Buscar assinante por telefone

public function findSubscriberByPhone(string $telefone): ?array

Monta chamada GET para endpoint:

subscriber/?phone=NUMERO_NORMALIZADO

Se encontrar, retorna array com dados do subscriber.

Se não, retorna null.

Criação de assinante (subscriber)

public function createSubscriber(string $nome, string $telefone, ?string $tagId = null): ?array

Endpoint:

subscriber/create/

Payload típico:

{
    "full_name": "Nome do Cliente",
    "phone": "5511999999999",
    "tags": [123456] // opcional
}

Itens:

phone deve estar no padrão internacional (55 + DDD + número).

Se origin_tag_id estiver configurado em services.botconversa, adiciona no array de tags.

opcionalmente: tags (ex.: para marcar origem “Sistema de Revenda”)

Retorna o payload criado ou null em caso de erro.

Enviar mensagem para assinante

public function sendMessageToSubscriber(string $subscriberId, string $mensagem): bool

POST subscriber/{subscriber_id}/send_message/

Payload:

{
    "type": "text",
    "message": "Conteúdo da mensagem"
}

Fluxo completo: telefone → assinante → mensagem

public function enviarParaTelefone(string $telefoneBruto, string $mensagem, ?string $nome = null): bool

Normaliza telefone.

Tenta findSubscriberByPhone.

Se não existir, chama createSubscriber.

Extrai subscriber_id e envia via sendMessageToSubscriber.

Atalho por cliente (opcional)

public function enviarParaCliente(Cliente $cliente, string $mensagem): bool

Extrai telefone do cliente (telefone, phone, whatsapp, etc).

Chama enviarParaTelefone.

Fonte do telefone do cliente

O Sistema busca telefone do Cliente em:

$cliente->telefone

$cliente->phone

$cliente->whatsapp

Nessa prioridade, por exemplo (depende da implementação).

Se não encontrar nenhum telefone:

Registra log de warning.

Não tenta enviar para BotConversa.

Camada de Mensagens de Campanha (MensagensCampanhaService)

Arquivo: app/Services/Whatsapp/MensagensCampanhaService.php

Objetivo:

Centralizar templates de mensagens relacionados a campanhas (em especial Campanha de Indicação).

Envia mensagens “semi-prontas”, com textos ricos, emojis, interpolando dados de cliente, pedido e campanha.

Responsabilidades:

Montar textos padronizados (template) para:

Mensagem de boas-vindas (se houver).

Mensagem de pedido criado para o cliente.

Aviso para o indicador quando o indicado faz o primeiro pedido (pendente).

Aviso ao indicador quando o pedido do indicado é entregue (prêmio/PIX).

Recibo de entrega para o cliente.

Registrar log em appmensagens (Model Mensagem).

Delegar envio efetivo para BotConversaService.

Assinatura típica de métodos

public function enviarAvisoIndicadorPedidoPendente(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido): bool

public function enviarAvisoIndicadorPedidoEntregue(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido, float $valorPremio): bool

public function enviarReciboEntregaCliente(PedidoVenda $pedido): bool

public function enviarMensagemBoasVindas(Cliente $cliente): bool

Cada método:

Monta o texto da mensagem (com base em templates e regras, ex.: valor, datas, descrição de campanha).

Registra o envio em appmensagens:

tipo: algo como "campanha_indicacao_pedido_pendente", "campanha_indicacao_pedido_entregue", "recibo_entrega_cliente", "boas_vindas".

cliente_id: indicador ou cliente (dependendo do caso).

pedido_id: se houver relação.

campanha_id: se for referente a campanha específica (indicação ou outra).

Assim mantém rastreabilidade.

Chama BotConversaService->enviarParaCliente($cliente, $texto).

Registro na tabela appmensagens

O fluxo típico para registrar mensagem é:

Criar instância de Mensagem::create([...]) ou usar um helper interno como MensageriaService.

Atribuições principais:

canal = 'whatsapp'.

direcao = 'outbound'.

tipo = identificador da regra/rotina (boas_vindas, aviso_indicador_pendente etc).

conteudo = texto enviado.

provider = 'botconversa'.

status = 'sent' ou 'queued' conforme o retorno de BotConversa.

provider_message_id / provider_status = preenchidos se o provedor retornar esses dados.

sent_at = now() se enviado com sucesso.

failed_at = now() em caso de falha, status = 'failed'.

ClienteObserver: disparo de mensagem de boas-vindas

Arquivo: app/Observers/ClienteObserver.php

Objetivo:

Ao criar um Cliente, automaticamente:

Tentar cadastrar/atualizar no BotConversa;

Enviar mensagem de boas-vindas;

Adicionar TAG de origem (opcional).

Trecho simplificado (fluxo atual)

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

        // 1) Tenta achar o contato no BotConversa
        $subscriber = $botConversa->findSubscriberByPhone($telefone);

        if (!$subscriber) {
            // 2) Se não existir, cria assinante com tag de origem, se configurada
            $subscriber = $botConversa->createSubscriber(
                $cliente->nome ?? $cliente->name ?? 'Cliente',
                $telefone,
                $originTagId
            );
        }

        // 3) Envia mensagem de boas-vindas, se desejado
        /** @var MensagensCampanhaService $mensagens */
        $mensagens = app(MensagensCampanhaService::class);
        $mensagens->enviarMensagemBoasVindas($cliente);

    } catch (\Throwable $e) {
        Log::error('Erro no ClienteObserver@created (BotConversa)', [
            'cliente_id' => $cliente->id ?? null,
            'erro'       => $e->getMessage(),
        ]);
    }
}

Comentários importantes:

origem_cadastro

Campo no Cliente que pode indicar se cadastro veio do próprio app, de importação, indicação, etc.

No caso, foi usado para a lógica clienteVindoDoApp.

Se origem_cadastro != 'app', é considerado vindo de fora (por exemplo, via formulário público, integração etc).

TAG de origem

Se services.botconversa.origin_tag_id estiver definido, o createSubscriber adiciona essa TAG ao cliente.

Isso ajuda a filtrar os contatos vindo do appRevenda dentro do painel BotConversa.

MensagensCampanhaService

É chamado para montar e enviar a mensagem de boas-vindas.

Internamente, esse serviço deve:

Montar o texto.

Registrar em appmensagens.

Chamar BotConversaService para efetivo envio.

PedidoVendaObserver: disparo de mensagens de campanha de indicação

Arquivo: app/Observers/PedidoVendaObserver.php

Objetivo:

Integrar criação de PedidoVenda com Campanhas, especialmente a campanha de indicação.

Fluxos principais:

Ao criar pedido (created):

Se for pedido indicado (indicador_id presente e campanha de indicação ativa):

Verifica se é primeira compra indicada do cliente;

Se for primeira compra pura (não vale se cliente já comprou sem indicador antes):

Dispara mensagem para o INDICADOR avisando que o indicado fez um pedido (status pendente).

Ao atualizar pedido para ENTREGUE (updated):

Se for pedido indicado e era primeira compra indicada:

Calcula o prêmio de indicação;

Dispara mensagem para o INDICADOR com o valor do prêmio/PIX;

Também dispara recibo de entrega para o CLIENTE.

Trechos principais (versão consolidada)

created(PedidoVenda $pedido)

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
                    'pedido_id'   => $pedido->id,
                    'indicador_id'=> $indicador->id,
                    'indicado_id' => $indicado->id,
                    'ok'          => $ok,
                ]);
            } else {
                Log::warning('Campanha indicação: pedido com indicador ou cliente ausente', [
                    'pedido_id' => $pedido->id,
                ]);
            }
        }

    } catch (\Throwable $e) {
        Log::error('Erro no PedidoVendaObserver@created para campanha de indicação', [
            'pedido_id' => $pedido->id ?? null,
            'erro'      => $e->getMessage(),
        ]);
    }
}

updated(PedidoVenda $pedido)

public function updated(PedidoVenda $pedido): void
{
    try {
        // Quando status mudar para ENTREGUE, tratar:
        if ($pedido->wasChanged('status') && $pedido->status === 'ENTREGUE') {
            $this->processarEntregaPedido($pedido);
        }

    } catch (\Throwable $e) {
        Log::error('Erro no PedidoVendaObserver@updated', [
            'pedido_id' => $pedido->id ?? null,
            'erro'      => $e->getMessage(),
        ]);
    }
}

private function processarEntregaPedido(PedidoVenda $pedido): void
{
    // 1) Campanha de indicação: aviso de prêmio
    if ($this->deveDispararIndicacao($pedido)) {

        $indicador = $pedido->indicador;
        $indicado  = $pedido->cliente;

        if ($indicador && $indicado && $this->ehPrimeiraCompraIndicada($pedido)) {

            // Calcula valor do prêmio
            $valorPremio = app(CampanhaService::class)
                ->calcularPremioIndicacao($pedido);

            /** @var MensagensCampanhaService $mensagens */
            $mensagens = app(MensagensCampanhaService::class);

            $ok = $mensagens->enviarAvisoIndicadorPedidoEntregue(
                $indicador,
                $indicado,
                $pedido,
                $valorPremio
            );

            Log::info('Campanha indicação: msg ENTREGUE enviada ao indicador', [
                'pedido_id'    => $pedido->id,
                'indicador_id' => $indicador->id,
                'indicado_id'  => $indicado->id,
                'valor_premio' => $valorPremio,
                'ok'           => $ok,
            ]);
        }
    }

    // 2) Recibo de entrega para o cliente
    /** @var MensagensCampanhaService $mensagens */
    $mensagens = app(MensagensCampanhaService::class);
    $okRecibo  = $mensagens->enviarReciboEntregaCliente($pedido);

    Log::info('Recibo de entrega enviado ao cliente', [
        'pedido_id' => $pedido->id,
        'ok'        => $okRecibo,
    ]);
}

Condições auxiliares

deveDispararIndicacao(PedidoVenda $pedido)

Regra simplificada:

deve ter indicador_id preenchido;

pedido deve estar vinculado a uma campanha de indicação ativa;

não deve estar marcado como “não participa”;

etc.

ehPrimeiraCompraIndicada(PedidoVenda $pedido)

Consulta no banco todos os pedidos com:

cliente_id = do indicado,

indicador_id = mesmo indicador,

status finalizado (ENTREGUE ou PAGO, conforme regra),

Se só existe esse pedido, ou se este é o primeiro com indicador (dependendo da modelagem), retorna true.

Campanha de indicação (CampanhaService, appcampanha, appcampanha_tipo)

Existem duas tabelas centrais para campanhas:

appcampanha

appcampanha_tipo

appcampanha_tipo define o “tipo lógico” de campanha, incluindo um campo metodo_php, que aponta para a função a ser usada na regra.

Exemplo:

id: 1

nome: "Campanha de Indicação"

metodo_php: "isCampanhaIndicacao"

appcampanha define campanhas específicas:

id

nome

tipo_id → appcampanha_tipo.id

ativa (1/0)

prioridade (int)

vigencia_inicio, vigencia_fim etc.

Regra de identificação da campanha de indicação:

No código:

- procurar uma Campanha ativa cujo tipo .metodo_php = 'isCampanhaIndicacao'.

É usada função no controller/serviço:

private function getCampanhaIndicacaoId(): ?int
{
    $id = DB::table('appcampanha as c')
        ->join('appcampanha_tipo as t', 't.id', '=', 'c.tipo_id')
        ->where('t.metodo_php', 'isCampanhaIndicacao')
        ->where('c.ativa', 1)
        ->orderByDesc('c.prioridade')
        ->value('c.id');

    // retorna o ID da campanha de indicação (ou null se não existir)
}

A regra é: campanha ativa cujo tipo (appcampanha_tipo) tem metodo_php = 'isCampanhaIndicacao'.

Cálculo do prêmio de indicação

Ainda no PedidoVendaController:

A rotina de conclusão/entrega de pedido usa CampanhaService::calcularPremioIndicacao($pedido).

A tabela de faixas de premiação (exemplo) é:

Compras entre R$ 50,00 e R$ 199,99 → 5% de comissão para quem indicou

Compras entre R$ 200,00 e R$ 399,99 → 6%

Compras entre R$ 400,00 e R$ 599,99 → 7%

Compras entre R$ 600,00 e R$ 799,99 → 8%

Compras entre R$ 800,00 e R$ 999,99 → 9%

Compras acima de R$ 1.000,00 → 10%

Implementação (ideia):

public function calcularPremioIndicacao(PedidoVenda $pedido): float
{
    $valorLiquido = $pedido->valor_liquido ?? $pedido->valor_total ?? 0.0;

    // Exemplo de faixas fixas:
    if ($valorLiquido >= 50 && $valorLiquido <= 199.99) {
        $percentual = 0.05;
    } elseif ($valorLiquido >= 200 && $valorLiquido <= 399.99) {
        $percentual = 0.06;
    } elseif (...) {
        ...
    } else {
        $percentual = 0.10; // acima de 1000
    }

    return round($valorLiquido * $percentual, 2);
}

Esse percentual e as faixas podem ser parametrizados em tabela específica (appcampanha_premio ou similar), permitindo configurar sem alterar código.

Fluxo de conclusão de pedido (PedidoVendaController)

Arquivo: app/Http/Controllers/PedidoVendaController.php

No método de entrega/conclusão (por exemplo concluirPedido ou entregarPedido):

Valida se o pedido está em status pendente (PENDENTE, ABERTO ou RESERVADO).

Em transação:

Chama estoque->confirmarSaidaVenda($pedido) (baixa estoque definitivo).

Muda status para ENTREGUE e salva.

Checa se continua sendo primeira compra indicada (ehPrimeiraCompraIndicada).

Executa atualizarIndicacaoParaPedido($pedido, $primeiraCompraIndicada).

Gera contas a receber ($this->cr->gerarParaPedido($pedido)).

Reavalia campanhas (CampaignEvaluatorService).

Ao final da transação:

O Observer será disparado (PedidoVendaObserver@updated), enviando as mensagens pertinentes.

Registro de mensagens no fluxo do pedido

A maior parte das mensagens relacionadas ao Pedido de Venda é centralizada em MensagensCampanhaService:

Avisos ao indicador (pendente / entregue).

Recibo de entrega ao cliente.

Eventualmente, confirmações de pedido ou boletos (caso implementado).

Cada envio:

Gera um registro em appmensagens, com:

cliente_id = cliente ou indicador.

pedido_id = pedido atual.

campanha_id = id da campanha de indicação ou outra campanha envolvida (se aplicável).

tipo = chave única interna do template.

conteudo = texto efetivamente enviado.

provider = 'botconversa'.

status, provider_message_id, provider_status, sent_at.

Relatórios sobre mensagens

Objetivo dos relatórios

Permitir que o usuário visualize:

Quantas mensagens foram enviadas por campanha, tipo, período.

Qual cliente recebeu quais mensagens.

Quais pedidos tiveram mensagens associadas.

Quais mensagens falharam.

Implementação base

Controller: RelatorioMensagensController

Arquivo: app/Http/Controllers/RelatorioMensagensController.php

Rotas típicas:

GET /relatorios/mensagens/por-campanha

GET /relatorios/mensagens/resumo

Pontos principais:

Recebe filtros via query string:

tipo

campanha_id

cliente_id

pedido_id

canal

direcao

status

data_de (range em sent_at)

data_ate

Monta uma query base em Mensagem:

$query = Mensagem::query()
    ->with(['cliente', 'pedido', 'campanha'])
    ->when($request->filled('tipo'), fn($q) => $q->where('tipo', $request->tipo))
    ->when($request->filled('campanha_id'), fn($q) => $q->where('campanha_id', $request->campanha_id))
    ->when($request->filled('cliente_id'), fn($q) => $q->where('cliente_id', $request->cliente_id))
    ...

Filtra por data de envio (sent_at):

if ($request->filled('data_de')) {
    $query->whereDate('sent_at', '>=', $request->data_de);
}
if ($request->filled('data_ate')) {
    $query->whereDate('sent_at', '<=', $request->data_ate);
}

Ordena por sent_at desc:

$query->orderByDesc('sent_at');

Relatório “Mensagens por Campanha”

Rota
Route::get('relatorios/mensagens/por-campanha', [RelatorioMensagensController::class, 'porCampanha'])
    ->name('relatorios.mensagens.por_campanha');

Controller: RelatorioMensagensController@porCampanha

Arquivo: app/Http/Controllers/RelatorioMensagensController.php

Este método:

Lê filtros da query string;

Monta uma query base em Mensagem com todos os filtros aplicados;

A partir dessa query, calcula:

resumoPorCampanha (agregado por campanha);

resumoPorTipo (agregado por tipo);

listaPaginada (lista detalhada das mensagens).

Exemplo de agregação por campanha

$resumoPorCampanha = (clone $query)
    ->selectRaw('campanha_id, COUNT(*) as total, 
        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as total_enviadas,
        SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as total_falhas'
    )
    ->groupBy('campanha_id')
    ->get();

Exemplo de agregação por tipo

$resumoPorTipo = (clone $query)
    ->selectRaw('tipo, COUNT(*) as total')
    ->groupBy('tipo')
    ->get();

Lista paginada

$mensagens = $query->paginate(50);

Envia tudo para a view relatorios.mensagens_por_campanha.

View: resources/views/relatorios/mensagens_por_campanha.blade.php

{{-- resources/views/relatorios/mensagens_por_campanha.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Relatório de Mensagens por Campanha
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-4">

        {{-- Filtros --}}
        <form method="GET" action="{{ route('relatorios.mensagens.por_campanha') }}" class="row g-3 mb-4">

            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <input type="text"
                       name="tipo"
                       value="{{ $filtros['tipo'] ?? '' }}"
                       class="form-control"
                       placeholder="Ex.: campanha_indicacao_pedido_pendente">
            </div>

            <div class="col-md-3">
                <label class="form-label">Canal</label>
                <input type="text"
                       name="canal"
                       value="{{ $filtros['canal'] ?? '' }}"
                       class="form-control"
                       placeholder="Ex.: whatsapp">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="sent"     {{ ($filtros['status'] ?? '') === 'sent' ? 'selected' : '' }}>Enviadas</option>
                    <option value="failed"   {{ ($filtros['status'] ?? '') === 'failed' ? 'selected' : '' }}>Falhas</option>
                    <option value="queued"   {{ ($filtros['status'] ?? '') === 'queued' ? 'selected' : '' }}>Na fila</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Direção</label>
                <select name="direcao" class="form-select">
                    <option value="">Todas</option>
                    <option value="outbound" {{ ($filtros['direcao'] ?? '') === 'outbound' ? 'selected' : '' }}>Enviadas</option>
                    <option value="inbound"  {{ ($filtros['direcao'] ?? '') === 'inbound' ? 'selected' : '' }}>Recebidas</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Data inicial (sent_at)</label>
                <input type="date"
                       name="data_de"
                       value="{{ $filtros['data_de'] ?? '' }}"
                       class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Data final (sent_at)</label>
                <input type="date"
                       name="data_ate"
                       value="{{ $filtros['data_ate'] ?? '' }}"
                       class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Campanha</label>
                <select name="campanha_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($campanhas as $campanha)
                        <option value="{{ $campanha->id }}"
                            {{ ($filtros['campanha_id'] ?? '') == $campanha->id ? 'selected' : '' }}>
                            {{ $campanha->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    Filtrar
                </button>
            </div>

        </form>

        {{-- Resumo por campanha --}}
        <h3 class="h5 mt-4 mb-2">Resumo por Campanha</h3>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Campanha</th>
                        <th>Total</th>
                        <th>Enviadas</th>
                        <th>Falhas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumoPorCampanha as $linha)
                        <tr>
                            <td>{{ optional($linha->campanha)->nome ?? '—' }}</td>
                            <td>{{ $linha->total }}</td>
                            <td>{{ $linha->total_enviadas }}</td>
                            <td>{{ $linha->total_falhas }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Nenhum dado encontrado para os filtros informados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Resumo por tipo --}}
        <h3 class="h5 mt-4 mb-2">Resumo por Tipo de Mensagem</h3>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumoPorTipo as $linha)
                        <tr>
                            <td>{{ $linha->tipo }}</td>
                            <td>{{ $linha->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Nenhum dado encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Lista detalhada --}}
        <h3 class="h5 mt-4 mb-2">Mensagens (Detalhes)</h3>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Data envio</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Campanha</th>
                        <th>Tipo</th>
                        <th>Canal</th>
                        <th>Status</th>
                        <th>Conteúdo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mensagens as $msg)
                        <tr>
                            <td>{{ $msg->sent_at ? $msg->sent_at->format('d/m/Y H:i') : '—' }}</td>
                            <td>{{ optional($msg->cliente)->nome ?? '—' }}</td>
                            <td>{{ optional($msg->pedido)->id ?? '—' }}</td>
                            <td>{{ optional($msg->campanha)->nome ?? '—' }}</td>
                            <td>{{ $msg->tipo }}</td>
                            <td>{{ $msg->canal }}</td>
                            <td>{{ $msg->status }}</td>
                            <td style="max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $msg->conteudo }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Nenhuma mensagem encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $mensagens->withQueryString()->links() }}
        </div>

    </div>
</x-app-layout>

Resumo global em Mensagens (com filtros tipo, campanha_id e período)

Além do relatório por campanha, é possível ter um endpoint/relatório mais genérico, por exemplo:

GET /relatorios/mensagens

Com filtros:

tipo (string)

campanha_id (int)

cliente_id (int)

pedido_id (int)

status (string)

data_de / data_ate (sent_at)

Exemplo de Controller

public function index(Request $request)
{
    $query = Mensagem::query()->with(['cliente', 'pedido', 'campanha']);

    if ($request->filled('tipo')) {
        $query->where('tipo', $request->tipo);
    }

    if ($request->filled('campanha_id')) {
        $query->where('campanha_id', $request->campanha_id);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('data_de')) {
        $query->whereDate('sent_at', '>=', $request->data_de);
    }

    if ($request->filled('data_ate')) {
        $query->whereDate('sent_at', '<=', $request->data_ate);
    }

    $mensagens = $query->orderByDesc('sent_at')->paginate(50);

    return view('relatorios.mensagens.index', [
        'mensagens' => $mensagens,
        'filtros'   => $request->all(),
    ]);
}

A view relatorios/mensagens/index.blade.php pode listar todas as mensagens (sem resumir por campanha), com filtros e paginação.

Pontos importantes e boas práticas da mensageria

1) Sempre registrar em appmensagens

Qualquer mensagem enviada automaticamente (campanha, recibo, lembretes etc.) deve:

Ser registrada em appmensagens com os devidos vínculos (cliente, pedido, campanha);

Ter tipo (string) descritivo e estável;

Ter canal = 'whatsapp' (no atual contexto);

Registrar status inicial 'sent' ou 'queued' e timestamps (sent_at, etc).

2) Agrupar templates de WhatsApp em serviços/coisas específicas

Em vez de espalhar texto de WhatsApp dentro de controllers, observers etc.:

Centralizar no MensagensCampanhaService (ou serviços específicos por domínio);

Usar métodos semânticos: enviarReciboEntregaCliente, enviarAvisoIndicadorPedidoPendente etc;

Facilita manutenção de texto, emojis, links de pagamento, etc.

3) Preparar espaço para outros provedores

Como o Model Mensagem já tem:

provider

provider_subscriber_id

provider_message_id

provider_status

É possível no futuro adicionar Z-API, Twilio etc. mantendo um provider diferente, com um adaptador para cada.

4) Trabalhar com filas (queues) no futuro

Para alto volume de mensagens, ideal usar o sistema de filas do Laravel:

Cada envio registra a mensagem e despacha um Job;

O Job efetivamente chama BotConversaService;

Atualiza status, provider_message_id, timestamps.

No início, pode ser síncrono, mas a estrutura já está preparada para assíncrono.

5) Logs e segurança

Sempre logar:

Erros de integração com BotConversa.

Situações de cliente sem telefone.

Situações em que o pedido não está apto para campanh
