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
.env
BOTCONVERSA_BASE_URL="https://backend.botconversa.com.br"
BOTCONVERSA_API_KEY="SUA-API-KEY-AQUI"
BOTCONVERSA_TAG_ORIGEM_SISTEMA="123"   # opcional: ID da tag de origem, se usar


Obs: nunca versionar a API KEY real.

config/services.php
'botconversa' => [
    'base_url' => env('BOTCONVERSA_BASE_URL', 'https://backend.botconversa.com.br'),
    'api_key'  => env('BOTCONVERSA_API_KEY'),
],

Serviço BotConversaService

Arquivo: app/Services/Whatsapp/BotConversaService.php

Responsável por falar direto com a API do BotConversa:

Principais responsabilidades

Normalizar telefone

Mantém apenas dígitos.

Se vier com 11 dígitos (DDD + número), prefixa 55 (DDI Brasil).

private function normalizePhone(?string $telefoneBruto): ?string


Buscar assinante por telefone

public function findSubscriberByPhone(string $telefoneBruto): ?array


GET subscriber/get_by_phone/{phone}/

Usa header API-KEY com a chave da API.

Retorna o payload do assinante ou null se não encontrar.

Criar assinante no BotConversa

public function createSubscriber(string $telefoneBruto, ?string $nome = null): ?array


POST subscriber/

Payload:

phone (com DDI + DDD + número)

first_name

last_name

name

opcionalmente: tags (ex.: para marcar origem “Sistema de Revenda”)

Retorna o payload criado ou null em caso de erro.

Enviar mensagem para assinante

public function sendMessageToSubscriber(string $subscriberId, string $mensagem): bool


POST subscriber/{subscriber_id}/send_message/

Payload:

{
  "type": "text",
  "value": "conteúdo da mensagem"
}


Fluxo completo: telefone → assinante → mensagem

public function enviarParaTelefone(string $telefoneBruto, string $mensagem, ?string $nome = null): bool


Normaliza telefone.

Tenta findSubscriberByPhone.

Se não existir, chama createSubscriber.

Extrai subscriber_id e envia via sendMessageToSubscriber.

Atalho por cliente (opcional)

Em vários pontos usamos:

$this->botConversa->enviarParaCliente($cliente, $conteudo);


Geralmente esse método:

Resolve o telefone do cliente (whatsapp, telefone, celular).

Usa enviarParaTelefone internamente.

Cadastro automático de contatos no BotConversa
ClienteObserver

Arquivo: app/Observers/ClienteObserver.php

Registrado no AppServiceProvider:

use App\Models\Cliente;
use App\Observers\ClienteObserver;

public function boot(): void
{
    Cliente::observe(ClienteObserver::class);
}

Regra no created()

Quando um novo cliente é criado:

Recupera telefone do cliente (telefone ou phone).

Usa BotConversaService para criar subscriber:

$subscriber = $botConversa->createSubscriber($telefone, $cliente->nome);


Opcionalmente:

Associa uma tag (ex.: “Cadastro via Sistema de Revenda”).

Envia mensagem de boas-vindas.

Isso garante que todo cliente novo já tem contato no BotConversa, pronto para receber campanhas.

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

Relacionamentos
public function cliente()
{
    return $this->belongsTo(Cliente::class);
}

public function pedido()
{
    return $this->belongsTo(PedidoVenda::class, 'pedido_id');
}

public function campanha()
{
    return $this->belongsTo(Campanha::class, 'campanha_id');
}

Scopes de filtro (para relatórios)

Exemplos implementados:

scopeDoPeriodo($de, $ate) — filtra por data (sent_at).

scopeStatus($status) — sent, failed, queued.

scopeTipo($tipo) — ex.: pedido_pendente_cliente, indicacao_primeira_compra.

scopeCanal($canal) — ex.: whatsapp.

scopeDirecao($direcao) — outbound / inbound.

scopePorCliente($clienteId)

scopePorPedido($pedidoId)

scopePorCampanha($campanhaId)

scopeBuscaLivre($busca) — busca em conteudo + nome do cliente.

Serviço MensageriaService

Arquivo: app/Services/MensageriaService.php

Essa camada faz a ponte entre regra de negócio e BotConversa, garantindo que:

Tudo que for enviado via WhatsApp seja registrado em appmensagens;

O envio use sempre o BotConversaService.

Assinatura
public function enviarWhatsapp(
    Cliente $cliente,
    string $conteudo,
    ?string $tipo = null,
    ?PedidoVenda $pedido = null,
    ?Campanha $campanha = null,
    array $payloadExtra = []
): Mensagem

Fluxo

Cria registro em appmensagens com status queued:

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


Envia via BotConversa:

$ok = $this->botConversa->enviarParaCliente($cliente, $conteudo);


Atualiza status interno:

Se $ok = true: status = 'sent', sent_at = now()

Se $ok = false: status = 'failed', failed_at = now()

Retorna o model Mensagem.

Qualquer fluxo novo de mensagem (nova campanha, lembrete, etc.) deve usar essa service para manter o log consistente.

Serviço MensagensCampanhaService

Arquivo: app/Services/Whatsapp/MensagensCampanhaService.php

Responsável por montar o texto das mensagens relacionadas a campanhas de indicação.

Mensagem: pedido pendente (primeira compra indicada)
public function montarMensagemPedidoPendente(
    Cliente $indicador,
    Cliente $indicado,
    PedidoVenda $pedido,
    ?float $valorPremio = null
): string


Texto enviado ao indicador quando o indicado faz a primeira compra (pedido PENDENTE).

Inclui:

Nome do indicador e indicado

Nº do pedido

Data do pedido

Valor do pedido

Frase sobre o prêmio (com valor, se $valorPremio informado)

Mensagem: prêmio disponível (pedido entregue)
public function montarMensagemPremioDisponivel(
    Cliente $indicador,
    Cliente $indicado,
    PedidoVenda $pedido,
    ?float $valorPremio = null
): string


Texto enviado ao indicador quando o pedido do indicado é ENTREGUE.

Inclui:

Nº do pedido

Valor

Data da entrega

Valor do prêmio (se disponível)

Solicitação de chave PIX

Campanhas de indicação
Modelo Campanha

Arquivo: app/Models/Campanha.php
Tabela: appcampanha

Campos importantes:

nome, descricao

metodo_php (ex.: isCampanhaIndicacao)

tipo_id (relaciona com appcampanha_tipo)

data_inicio, data_fim

ativa

prioridade

Regras auxiliares:

emVigencia() — campanha ativa e dentro do período

isCumulativa() — indica se pode acumular com outras

Identificando a campanha de indicação

No PedidoVendaController:

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

private function calcularPremioIndicacao(float $valorPedido): float
{
    $faixas = [
        [0.00,    99.99,   5.00],
        [100.00,  199.99, 10.00],
        [200.00,  399.99, 20.00],
        [400.00,  599.99, 40.00],
        [600.00,  799.99, 60.00],
        [800.00,  999.99, 80.00],
        [1000.00, 9999.99, 100.00],
    ];

    foreach ($faixas as [$min, $max, $premio]) {
        if ($valorPedido >= $min && $valorPedido <= $max) {
            return $premio;
        }
    }

    return 0.0;
}

Lógica de pedidos e indicação
ehPrimeiraCompraIndicada(PedidoVenda $pedido): bool

Regra para saber se este pedido entra na campanha de indicação:

indicador_id diferente de 1 (1 = “sem indicador padrão”).

Não existir nenhum outro pedido do mesmo cliente:

$jaTemQualquerPedido = PedidoVenda::where('cliente_id', $pedido->cliente_id)
    ->where('id', '!=', $pedido->id)
    ->exists();


Se não houver outro pedido → é primeira compra indicada.

Atualizar/gerenciar Indicacao

Função central:

function atualizarIndicacaoParaPedido(PedidoVenda $pedido, bool $criarSeNaoExistir = false): void


Regras:

Só atua se:

status do pedido = ENTREGUE;

indicador_id != 1;

valor do pedido > 0.

Fluxo:

Busca a campanha de indicação (getCampanhaIndicacaoId()).

Busca Indicacao existente para (indicado_id, pedido_id).

Se não existe:

Se $criarSeNaoExistir = false → não faz nada.

Se $criarSeNaoExistir = true → cria nova Indicacao (status = 'pendente').

Se já está status = 'pago' → não altera (não recalcula).

Atualiza:

valor_pedido

valor_premio (via calcularPremioIndicacao)

campanha_id (se não estiver preenchido)

Fluxo: criação de pedido (PedidoVendaController@store)

Resumo do que acontece:

Valida request e recalcula itens pela ViewProduto.

Calcula:

valor_total (bruto)

valor_desconto (manual)

valor_liquido (bruto - desconto)

Pega indicador_id do cliente (padrão = 1).

Grava pedido em apppedidovenda com status PENDENTE.

Carrega PedidoVenda recém-criado (model).

Verifica se é primeira compra indicada (ehPrimeiraCompraIndicada).

Se for:

Busca a campanha de indicação (getCampanhaIndicacaoId).

Aplica desconto de 5% sobre o valor total:

Soma no valor_desconto.

Recalcula valor_liquido.

Define campanha_id.

Inclui observação: Desconto de 5% aplicado (primeira compra indicada).

Cria/atualiza registro em appindicacao:

indicado_id

indicador_id

pedido_id

valor_pedido (já com desconto)

valor_premio

campanha_id

status = 'pendente'

Envia mensagem ao indicador:

$this->enviarAvisoIndicadorWhatsApp($indicacao);


Usa MensageriaService + MensagensCampanhaService::montarMensagemPedidoPendente.

Registra em appmensagens com tipo = 'indicacao_primeira_compra'.

Independente de haver indicação ou não, envia mensagem ao cliente:

$this->enviarAvisoClientePedidoCriado($pedido);


Informa:

nº do pedido

valor

forma de pagamento

plano (se houver)

previsão de entrega

observação

Registra em appmensagens com tipo = 'pedido_pendente_cliente'.

Grava itens em appitemvenda.

Atualiza reservas de estoque (appestoque + appmovestoque).

Fluxo: confirmação de entrega (PedidoVendaController@confirmarEntrega)

Valida se o pedido está em status pendente (PENDENTE, ABERTO ou RESERVADO).

Em transação:

Chama estoque->confirmarSaidaVenda($pedido) (baixa estoque definitivo).

Muda status para ENTREGUE e salva.

Checa se continua sendo primeira compra indicada (ehPrimeiraCompraIndicada).

Executa atualizarIndicacaoParaPedido($pedido, $primeiraCompraIndicada).

Gera contas a receber ($this->cr->gerarParaPedido($pedido)).

Reavalia campanhas (CampaignEvaluatorService).

Fora da transação:

Envia recibo de entrega ao cliente:

$this->enviarReciboWhatsApp($pedido);


Via MensageriaService.

Tipo: pedido_entregue_cliente.

Em paralelo, o PedidoVendaObserver@updated (não mostrado inteiro aqui, mas existente) detecta a mudança de status para ENTREGUE e:

Se for campanha de indicação (deveDispararIndicacao):

Usa MensagensCampanhaService::montarMensagemPremioDisponivel.

Usa MensageriaService para notificar o indicador, pedindo chave PIX.

Registra em appmensagens (tipo algo como indicacao_premio_pix).

Relatórios de Mensagens
Controller: MensagemController

Arquivo: app/Http/Controllers/MensagemController.php

Método index(Request $request):

Recebe filtros:

data_de, data_ate (período de envio: sent_at)

status (queued, sent, failed)

tipo (ex.: pedido_pendente_cliente, indicacao_primeira_compra)

canal (whatsapp)

direcao (outbound / inbound)

cliente_id

campanha_id

pedido_id

busca (texto em conteudo + nome do cliente)

Monta query:

$query = Mensagem::with(['cliente:id,nome', 'campanha:id,nome', 'pedido:id,cliente_id'])
    ->orderByDesc('sent_at')
    ->orderByDesc('id');

$query
    ->doPeriodo(...)
    ->status(...)
    ->tipo(...)
    ->canal(...)
    ->direcao(...)
    ->porCliente(...)
    ->porCampanha(...)
    ->porPedido(...)
    ->buscaLivre(...);

$mensagens = $query->paginate(20)->appends($request->query());


Carrega também listas para combos:

clientes (id + nome)

campanhas (id + nome)

tiposConhecidos (distinct de tipo em appmensagens)

Renderiza view mensagens.index.

Rota
Route::get('/mensagens', [MensagemController::class, 'index'])
    ->name('mensagens.index');

View: mensagens.index

A view exibe:

Formulário de filtros:

Período

Status

Tipo

Campanha

Cliente

Pedido

Canal

Direção

Busca livre

Tabela com:

ID

Data de envio

Cliente

Pedido

Campanha

Tipo

Status (badge colorida)

Canal

Direção

Preview do conteúdo (ex.: primeiros 80 caracteres)

Paginação usando {{ $mensagens->links() }}.

Como estender pra novas campanhas/mensagens

Para criar um novo tipo de automação que use WhatsApp + BotConversa de forma consistente:

Definir tipo lógico

Ex.: boas_vindas_app, lembrete_pagamento, aniversario_cliente.

Criar texto em um service

Preferencialmente um service próprio (ex.: MensagensBoasVindasService) ou no MensagensCampanhaService se for campanha.

Usar sempre MensageriaService::enviarWhatsapp()

Exemplo:

$mensageria = app(MensageriaService::class);
$msgModel = $mensageria->enviarWhatsapp(
    cliente:  $cliente,
    conteudo: $texto,
    tipo:     'boas_vindas_app',
    pedido:   null,
    campanha: null,
    payloadExtra: [
        'origem' => 'cadastro_app',
    ],
);


Registrar qualquer inteligência de negócio (ex.: só enviar 1 vez, só para clientes de determinada campanha) no controller/service da regra, não dentro da MensageriaService.