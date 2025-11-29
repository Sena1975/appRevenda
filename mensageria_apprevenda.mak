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

Fluxo de Eventos (Swimlane)
Visão geral dos atores

Ao longo dos fluxos, vamos considerar esses “atores”:

Operador: usuário do sistema (você / revendedora) que cadastra cliente ou pedido.

App (Laravel): controllers, models, services.

BotConversaService: integração com API do BotConversa.

MensageriaService: registra e envia mensagens (tabela appmensagens).

BotConversa (API): plataforma que realmente dispara o WhatsApp.

1. Cadastro de cliente
Objetivo

Criar cliente no sistema.

Criar contato correspondente no BotConversa.

(Opcional) marcar tag de origem e/ou enviar mensagem de boas-vindas.

Swimlane textual

Operador

Acessa tela de “Novo Cliente”.

Preenche dados (nome, telefone, indicador etc.) e salva.

App (Laravel)

Cliente::create(...) é chamado.

Eloquent dispara o evento created para o model Cliente.

ClienteObserver@created(Cliente $cliente) é executado:

Resolve o telefone ($cliente->telefone / phone etc).

Se não houver telefone → registra log e não integra com BotConversa.

Se houver telefone:

Chama:

$subscriber = $botConversa->createSubscriber($telefone, $cliente->nome);


(Opcional) adiciona tag de origem se configurado.

BotConversaService

createSubscriber():

Normaliza telefone (normalizePhone → só dígitos, prefixa 55 se tiver 11 dígitos).

Monta payload:

[
  'phone'      => '55DDDNÚMERO',
  'first_name' => $firstName,
  'last_name'  => $lastName,
  'name'       => "$firstName $lastName",
  // 'tags' => [ID_TAG_ORIGEM], se configurado
]


Faz POST /subscriber/ com header API-KEY.

Se retornar 200/201 → devolve array com dados do assinante (incluindo id).

Caso contrário → log de erro e retorna null.

MensageriaService (Opcional)

Se você decidir enviar boas-vindas aqui:

Gera texto (ex.: “Olá, seu cadastro foi realizado com sucesso”).

Chama:

$mensageria->enviarWhatsapp(
    cliente:  $cliente,
    conteudo: $mensagem,
    tipo:     'boas_vindas_cliente',
    pedido:   null,
    campanha: null,
    payloadExtra: ['evento' => 'cadastro_cliente'],
);


MensageriaService → fluxo interno

Cria registro em appmensagens:

status = 'queued'

provider = 'botconversa'

canal = 'whatsapp'

direcao = 'outbound'

Usa BotConversaService->enviarParaCliente() para disparar o WhatsApp.

Atualiza status para:

sent + sent_at = now() se OK.

failed + failed_at = now() se erro.

BotConversa (API)

Recebe o envio (send_message) e entrega a mensagem ao usuário final (WhatsApp).

2. Criação de pedido sem indicação
Objetivo

Registrar pedido PENDENTE.

Registrar estoque reservado.

Enviar mensagem para o cliente com detalhes do pedido.

Registrar essa mensagem em appmensagens.

Swimlane textual

Operador

Acessa “Nova Venda”.

Seleciona cliente, produtos, forma/plano de pagamento, data, previsão etc.

Clica em Salvar.

App – PedidoVendaController@store()

Valida request e monta estrutura de itens.

Recalcula valores dos itens com base na ViewProduto:

preco_unitario

pontuacao

preco_total

Calcula totais:

valor_total (soma dos itens)

valor_desconto (manual da tela)

valor_liquido (total - desconto)

Determina indicador_id:

Pega do cliente ($cliente->indicador_id).

Se não tiver, usa 1 como “sem indicador”.

Grava cabeçalho em apppedidovenda:

status: PENDENTE

com cliente_id, valor_total, valor_desconto, valor_liquido, indicador_id, etc.

Carrega o model PedidoVenda via PedidoVenda::find($vendaId).

Regra de campanha de indicação

Chama:

$primeiraCompraIndicada = $this->ehPrimeiraCompraIndicada($pedido);


Nesse caso, sem indicação, o retorno será false (indicador_id = 1).

Como não é primeira compra indicada, nenhuma indicação é criada aqui.

Mensagem para o cliente (pedido criado)

Ainda no store(), após a parte de campanhas/indicação:

$this->enviarAvisoClientePedidoCriado($pedido);


enviarAvisoClientePedidoCriado():

Carrega cliente, forma, plano.

Monta texto com:

nº do pedido

data do pedido

valor

forma de pagamento

plano (se houver)

previsão de entrega

observação

Chama:

$mensageria->enviarWhatsapp(
    cliente:  $cliente,
    conteudo: $texto,
    tipo:     'pedido_pendente_cliente',
    pedido:   $pedido,
    campanha: $campanha, // se tiver
    payloadExtra: ['evento' => 'pedido_pendente_cliente'],
);


MensageriaService

Cria registro em appmensagens com tipo pedido_pendente_cliente.

Usa BotConversaService para enviar.

Atualiza status (sent/failed).

App – Estoque

Grava itens na appitemvenda.

Atualiza reserva em appestoque + grava movimentos em appmovestoque.

3. Criação de pedido com indicação (primeira compra)

Aqui é o fluxo especial de campanha de indicação.

Objetivo

Aplicar desconto de 5% ao pedido.

Registrar uma Indicacao (indicado, indicador, valor do pedido, valor do prêmio, campanha).

Enviar mensagem ao indicador avisando a primeira compra.

Enviar mensagem ao cliente com confirmação de pedido.

Registrar ambas as mensagens em appmensagens.

Swimlane textual

Operador

Cadastra um pedido para um cliente que:

Tem indicador_id != 1.

Nunca teve outro pedido.

App – PedidoVendaController@store()

Mesma lógica de cálculo de itens e totais do caso anterior.

Grava cabeçalho em apppedidovenda (PENDENTE, com indicador_id != 1).

Carrega $pedido = PedidoVenda::find($vendaId).

Regra de primeira compra indicada

Chama:

$primeiraCompraIndicada = $this->ehPrimeiraCompraIndicada($pedido);


Verifica se o cliente já teve outros pedidos.

Se não teve, retorna true.

Se true, busca:

$campanhaId = $this->getCampanhaIndicacaoId();


Procura campanha ativa com metodo_php = 'isCampanhaIndicacao'.

Se há campanha de indicação:

Calcula desconto de 5% sobre valor_total:

$descontoIndicacao = round($valorTotal * 0.05, 2);


Soma ao desconto atual:

$novoDesconto     = $descontoAtual + $descontoIndicacao;
$novoValorLiquido = max(0, $valorTotal - $novoDesconto);


Atualiza pedido:

valor_desconto = $novoDesconto

valor_liquido = $novoValorLiquido

campanha_id = $campanhaId

observacao += "Desconto de 5% aplicado (primeira compra indicada)."

Registro de indicação

Calcula valorPedidoIndicacao = $novoValorLiquido.

Busca Indicacao existente para (indicado_id, pedido_id).

Se não existir:

Cria nova Indicacao:

indicado_id = cliente

indicador_id = indicador

pedido_id = pedido

status = 'pendente'

Se status != 'pago':

Calcula valor_premio via calcularPremioIndicacao.

Define campanha_id.

Salva Indicacao.

Mensagem para o INDICADOR (primeira compra)

Chama:

$this->enviarAvisoIndicadorWhatsApp($indicacao);


enviarAvisoIndicadorWhatsApp():

Carrega $indicador (cliente que recebe o prêmio).

Carrega $indicado (cliente que fez a compra).

Carrega $pedido e Campanha (se tiver).

Garante que valor_premio > 0 antes de enviar.

Usa MensagensCampanhaService:

$texto = $msgCampanha->montarMensagemPedidoPendente(
    indicador:  $indicador,
    indicado:   $indicado,
    pedido:     $pedido,
    valorPremio:$valorPremio,
);


Chama MensageriaService->enviarWhatsapp(...) com:

tipo = 'indicacao_primeira_compra'

pedido_id e campanha_id da indicação

payloadExtra com:

evento = 'indicacao_primeira_compra'

indicacao_id

valor_premio

valor_pedido

MensageriaService registra em appmensagens e dispara pelo BotConversa.

Mensagem para o CLIENTE (pedido criado)

Ainda no store():

Ao final, chama:

$this->enviarAvisoClientePedidoCriado($pedido);


Mesmo fluxo descrito no caso anterior (tipo pedido_pendente_cliente).

App – Estoque

Grava itens e reservas normalmente (igual caso sem indicação).

4. Confirmação de entrega do pedido

Aqui acontece:

Baixa de estoque,

Geração de contas a receber,

Atualização/recalculo de indicação,

Recibo para o cliente,

Mensagem de prêmio para o indicador (solicitando PIX).

Swimlane textual

Operador

Acessa tela do pedido.

Clica em “Confirmar Entrega”.

App – PedidoVendaController@confirmarEntrega($id)

Valida se status atual está em [PENDENTE, ABERTO, RESERVADO].

Abre transação.

Dentro da transação:

estoque->confirmarSaidaVenda($pedido):

Ajusta appestoque / appmovestoque.

Muda status do pedido para ENTREGUE e salva.

Verifica se é primeira compra indicada:

$primeiraCompraIndicada = $this->ehPrimeiraCompraIndicada($pedido);


Chama:

$this->atualizarIndicacaoParaPedido($pedido, $primeiraCompraIndicada);


Se já existir Indicacao, recalcula valor_pedido / valor_premio.

Se não existir e $primeiraCompraIndicada = true, cria.

Gera contas a receber:

$this->cr->gerarParaPedido($pedido);


Reavalia campanhas (CampaignEvaluatorService).

Dá commit na transação.

Observer – PedidoVendaObserver@updated()

O Eloquent dispara o evento updated do PedidoVenda.

PedidoVendaObserver@updated($pedido) roda:

Verifica se status mudou (wasChanged('status')).

Se status === 'ENTREGUE':

Carrega cliente, indicador.

Verifica se a campanha é de indicação via deveDispararIndicacao($pedido).

Se for e se existir indicador:

Carrega MensagensCampanhaService e monta:

$mensagem = $mensagens->montarMensagemPremioDisponivel(
    $indicador,
    $cliente,
    $pedido,
    $valorPremioOpcional,
);


Usa MensageriaService->enviarWhatsapp() com:

tipo = 'indicacao_premio_pix' (ou similar)

pedido, campanha, payloadExtra com dados da indicação.

Registra log:

'Campanha indicação: msg PRÊMIO registrada/enviada ao indicador'


Recibo para o CLIENTE (fora da transação)

Ainda no controller, após commit:

$this->enviarReciboWhatsApp($pedido);


enviarReciboWhatsApp():

Carrega cliente.

Busca parcelas em appcontasreceber (ContasReceber::where('pedido_id', ...)).

Monta mensagem com:

Confirmação de entrega (data de entrega = now()).

Valor final.

Lista de parcelas (nº, vencimento, valor).

Observação do pedido.

Usa MensageriaService->enviarWhatsapp() com:

tipo = 'pedido_entregue_cliente'.

payloadExtra com evento = 'pedido_entregue_cliente' e data_entrega.

MensageriaService salva em appmensagens e envia via BotConversa.

5. Cancelamento / exclusão de pedido (impacto em indicações)
Objetivo

Evitar inconsistência quando a indicação já foi paga.

Ajustar estoque, CR e indicação quando o pedido for cancelado/excluído.

Resumo

Cancelar pedido (cancelar):

Bloqueia se houver Indicacao com status = 'pago'.

Ajusta estoque (RESERVA_ENTRADA ou ENTRADA).

Cancela CR (appcontasreceber).

Atualiza Indicacao (não paga) para status = 'cancelado' + valor_premio = 0.

Excluir pedido (destroy):

Bloqueia se houver Indicacao paga.

Ajusta estoque (se PENDENTE).

Cancela CR.

Exclui Indicacao com status != 'pago'.

Obs.: Hoje não há disparo automático de mensagens para o indicador nessas ações (mas poderia ser adicionado usando o mesmo padrão: MensageriaService + texto específico).

Relatórios de Mensageria
Visão geral

A camada de mensageria registra todos os envios de WhatsApp em uma tabela dedicada (appmensagens, model Mensagem).
Isso permite criar relatórios por:

Campanha

Cliente

Tipo de mensagem

Status (sent / failed / queued)

Período

Esta seção documenta o relatório Mensagens por Campanha (controller + view).

Model base: Mensagem

Arquivo: app/Models/Mensagem.php

class Mensagem extends Model
{
    protected $table = 'appmensagens';

    protected $fillable = [
        'cliente_id',
        'pedido_id',
        'campanha_id',
        'canal',
        'direcao',
        'tipo',
        'conteudo',
        'payload',
        'provider',
        'provider_subscriber_id',
        'provider_message_id',
        'provider_status',
        'status',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];

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
}

Relatório: Mensagens por Campanha
Objetivo

Permite analisar, por campanha e período:

Quantas mensagens foram enviadas (sent), falharam (failed) ou estão em fila (queued);

Taxa de falha por campanha;

Quebra por tipo de mensagem (ex.: pedido_pendente_cliente, indicacao_primeira_compra, pedido_entregue_cliente, etc.);

Listagem detalhada das mensagens com campanha, cliente, pedido, status e conteúdo.

Rota

Arquivo: routes/web.php

use App\Http\Controllers\RelatorioMensagensController;

Route::get(
    '/relatorios/mensagens/campanhas',
    [RelatorioMensagensController::class, 'porCampanha']
)->name('relatorios.mensagens.por_campanha');

Controller: RelatorioMensagensController@porCampanha

Arquivo: app/Http/Controllers/RelatorioMensagensController.php

Este método:

Lê filtros da query string;

Monta uma query base em Mensagem com todos os filtros aplicados;

A partir dessa query, calcula:

resumoPorCampanha (agregado por campanha);

resumoPorTipo (agregado por tipo de mensagem);

Busca a lista paginada de mensagens (detalhe);

Envia tudo para a view relatorios.mensagens_por_campanha.

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Mensagem;
use App\Models\Campanha;

class RelatorioMensagensController extends Controller
{
    /**
     * Relatório: Mensagens por campanha
     */
    public function porCampanha(Request $request)
    {
        $dataDe     = $request->query('data_de');
        $dataAte    = $request->query('data_ate');
        $campanhaId = $request->query('campanha_id');
        $status     = $request->query('status');
        $tipo       = $request->query('tipo');

        // 1) QUERY BASE COM FILTROS
        $query = Mensagem::query()
            ->with(['campanha:id,nome', 'cliente:id,nome', 'pedido:id'])
            ->whereNotNull('campanha_id')
            ->where('canal', 'whatsapp')
            ->where('direcao', 'outbound');

        // Filtro por período (sent_at)
        if ($dataDe) {
            $query->whereDate('sent_at', '>=', $dataDe);
        }
        if ($dataAte) {
            $query->whereDate('sent_at', '<=', $dataAte);
        }

        if ($campanhaId) {
            $query->where('campanha_id', $campanhaId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        // 2) RESUMO POR CAMPANHA (APÓS FILTROS)
        $resumoPorCampanha = (clone $query)
            ->select(
                'campanha_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) as total_enviadas"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_falhas"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as total_queued")
            )
            ->groupBy('campanha_id')
            ->get();

        // 3) RESUMO GLOBAL POR TIPO (APÓS FILTROS)
        $resumoPorTipo = (clone $query)
            ->select(
                'tipo',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) as total_enviadas"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_falhas"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as total_queued")
            )
            ->groupBy('tipo')
            ->orderBy('tipo')
            ->get();

        // 4) LISTAGEM DETALHADA (PAGINADA)
        $mensagens = $query
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        // Auxiliares para filtros
        $campanhas = Campanha::orderBy('nome')->get(['id','nome']);

        $tiposConhecidos = Mensagem::whereNotNull('tipo')
            ->distinct()
            ->pluck('tipo')
            ->sort()
            ->values();

        return view('relatorios.mensagens_por_campanha', [
            'mensagens'         => $mensagens,
            'resumoPorCampanha' => $resumoPorCampanha,
            'resumoPorTipo'     => $resumoPorTipo,
            'campanhas'         => $campanhas,
            'tiposConhecidos'   => $tiposConhecidos,
            'filtros'           => [
                'data_de'     => $dataDe,
                'data_ate'    => $dataAte,
                'campanha_id' => $campanhaId,
                'status'      => $status,
                'tipo'        => $tipo,
            ],
        ]);
    }
}

View: resources/views/relatorios/mensagens_por_campanha.blade.php

Essa view mostra:

Filtros (período, campanha, status, tipo);

Resumo por campanha (total, enviadas, falhas, % falha);

Resumo por tipo de mensagem (opcional, mas já implementado);

Lista detalhada de mensagens.

{{-- resources/views/relatorios/mensagens_por_campanha.blade.php --}}
@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app') {{-- ajuste para o layout que você usa --}}

@section('content')
<div class="container">
    <h1 class="mb-4">Relatório de Mensagens por Campanha</h1>

    {{-- FILTROS --}}
    <div class="card mb-4">
        <div class="card-header">
            Filtros
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('relatorios.mensagens.por_campanha') }}" class="row g-3">

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
                                @selected(($filtros['campanha_id'] ?? null) == $campanha->id)>
                                {{ $campanha->nome }} (ID: {{ $campanha->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status da mensagem</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['queued','sent','failed'] as $st)
                            <option value="{{ $st }}"
                                @selected(($filtros['status'] ?? null) === $st)>
                                {{ strtoupper($st) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipo da mensagem</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tiposConhecidos as $tipo)
                            <option value="{{ $tipo }}"
                                @selected(($filtros['tipo'] ?? null) === $tipo)>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.mensagens.por_campanha') }}" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- RESUMO POR CAMPANHA --}}
    <div class="card mb-4">
        <div class="card-header">
            Resumo por campanha (após filtros)
        </div>
        <div class="card-body p-0">
            @if($resumoPorCampanha->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhuma mensagem encontrada para os filtros selecionados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Campanha</th>
                                <th>ID</th>
                                <th>Total</th>
                                <th>Enviadas (sent)</th>
                                <th>Falhas (failed)</th>
                                <th>Em fila (queued)</th>
                                <th>% Falha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumoPorCampanha as $r)
                                @php
                                    $totalBase = ($r->total_enviadas ?? 0) + ($r->total_falhas ?? 0);
                                    $taxaFalha = $totalBase > 0
                                        ? round(($r->total_falhas ?? 0) * 100 / $totalBase, 1)
                                        : 0;
                                    $camp = $campanhas->firstWhere('id', $r->campanha_id);
                                @endphp
                                <tr>
                                    <td>{{ $camp->nome ?? 'Campanha #'.$r->campanha_id }}</td>
                                    <td>{{ $r->campanha_id }}</td>
                                    <td>{{ $r->total }}</td>
                                    <td>{{ $r->total_enviadas }}</td>
                                    <td>{{ $r->total_falhas }}</td>
                                    <td>{{ $r->total_queued }}</td>
                                    <td>{{ $taxaFalha }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- RESUMO GLOBAL POR TIPO --}}
    @isset($resumoPorTipo)
    <div class="card mb-4">
        <div class="card-header">
            Resumo por tipo de mensagem (após filtros)
        </div>
        <div class="card-body p-0">
            @if($resumoPorTipo->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhum dado para exibir.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>Enviadas</th>
                                <th>Falhas</th>
                                <th>Em fila</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumoPorTipo as $rt)
                                <tr>
                                    <td>{{ $rt->tipo ?? '(sem tipo)' }}</td>
                                    <td>{{ $rt->total }}</td>
                                    <td>{{ $rt->total_enviadas }}</td>
                                    <td>{{ $rt->total_falhas }}</td>
                                    <td>{{ $rt->total_queued }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endisset

    {{-- LISTAGEM DETALHADA --}}
    <div class="card">
        <div class="card-header">
            Mensagens (detalhe)
        </div>
        <div class="card-body p-0">
            @if($mensagens->isEmpty())
                <p class="p-3 mb-0 text-muted">Nenhuma mensagem encontrada.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sent at</th>
                                <th>Status</th>
                                <th>Tipo</th>
                                <th>Campanha</th>
                                <th>Cliente</th>
                                <th>Pedido</th>
                                <th>Conteúdo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mensagens as $msg)
                                <tr>
                                    <td>{{ $msg->id }}</td>
                                    <td>
                                        @if($msg->sent_at)
                                            {{ $msg->sent_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($msg->status) {
                                                'sent'   => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'queued' => 'bg-secondary',
                                                default  => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $msg->status ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $msg->tipo ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($msg->campanha)
                                            {{ $msg->campanha->nome }} ({{ $msg->campanha_id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($msg->cliente)
                                            {{ $msg->cliente->nome }} (ID {{ $msg->cliente_id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($msg->pedido)
                                            #{{ $msg->pedido->id }}
                                        @elseif($msg->pedido_id)
                                            #{{ $msg->pedido_id }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span title="{{ $msg->conteudo }}">
                                            {{ Str::limit($msg->conteudo, 80) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $mensagens->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

Relatório: Mensagens por Cliente
Objetivo

Esse relatório mostra o histórico de mensagens (timeline) por cliente, permitindo:

Ver tudo o que já foi enviado via WhatsApp para um cliente;

Filtrar por período;

Filtrar por tipo de mensagem (ex.: pedido_pendente_cliente, pedido_entregue_cliente, indicacao_primeira_compra, etc.);

Filtrar por status (queued, sent, failed);

Ver vínculo com pedido e campanha, quando houver.

Usa a mesma tabela appmensagens (App\Models\Mensagem).

Rota

Arquivo: routes/web.php

use App\Http\Controllers\RelatorioMensagensController;

Route::get(
    '/relatorios/mensagens/clientes',
    [RelatorioMensagensController::class, 'porCliente']
)->name('relatorios.mensagens.por_cliente');

Controller: RelatorioMensagensController@porCliente

Arquivo: app/Http/Controllers/RelatorioMensagensController.php

Esse método:

Lê os filtros da query string (cliente_id, datas, status, tipo);

Monta uma query em Mensagem filtrando por cliente (quando informado);

Traz as relações de cliente, pedido e campanha;

Retorna a lista paginada de mensagens + dados auxiliares para filtros.

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mensagem;
use App\Models\Cliente;

class RelatorioMensagensController extends Controller
{
    // ...

    /**
     * Relatório: histórico de mensagens por cliente
     */
    public function porCliente(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        $dataDe    = $request->query('data_de');
        $dataAte   = $request->query('data_ate');
        $status    = $request->query('status');
        $tipo      = $request->query('tipo');

        $query = Mensagem::with(['cliente:id,nome', 'pedido:id,cliente_id', 'campanha:id,nome'])
            ->where('canal', 'whatsapp')
            ->where('direcao', 'outbound');

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        if ($dataDe) {
            $query->whereDate('sent_at', '>=', $dataDe);
        }

        if ($dataAte) {
            $query->whereDate('sent_at', '<=', $dataAte);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        $mensagens = $query
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->appends($request->query());

        // Auxiliares para filtros
        $clientes = Cliente::orderBy('nome')->get(['id','nome']);

        $tiposConhecidos = Mensagem::whereNotNull('tipo')
            ->distinct()
            ->pluck('tipo')
            ->sort()
            ->values();

        return view('relatorios.mensagens_por_cliente', [
            'mensagens'       => $mensagens,
            'clientes'        => $clientes,
            'tiposConhecidos' => $tiposConhecidos,
            'filtros'         => [
                'cliente_id' => $clienteId,
                'data_de'    => $dataDe,
                'data_ate'   => $dataAte,
                'status'     => $status,
                'tipo'       => $tipo,
            ],
        ]);
    }
}


Se esse controller já existe com o método porCampanha, é só adicionar esse método ao mesmo arquivo.

View: resources/views/relatorios/mensagens_por_cliente.blade.php

Essa tela mostra:

Filtro principal por cliente;

Período (sent_at);

Status;

Tipo de mensagem;

Lista de mensagens (timeline simplificada).

{{-- resources/views/relatorios/mensagens_por_cliente.blade.php --}}
@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app') {{-- ajuste se o layout for outro --}}

@section('content')
<div class="container">
    <h1 class="mb-4">Relatório de Mensagens por Cliente</h1>

    {{-- FILTROS --}}
    <div class="card mb-4">
        <div class="card-header">
            Filtros
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('relatorios.mensagens.por_cliente') }}" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">Selecione um cliente</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}"
                                @selected(($filtros['cliente_id'] ?? null) == $cli->id)>
                                {{ $cli->nome }} (ID: {{ $cli->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data inicial (sent_at)</label>
                    <input type="date"
                           name="data_de"
                           value="{{ $filtros['data_de'] ?? '' }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data final (sent_at)</label>
                    <input type="date"
                           name="data_ate"
                           value="{{ $filtros['data_ate'] ?? '' }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['queued','sent','failed'] as $st)
                            <option value="{{ $st }}"
                                @selected(($filtros['status'] ?? null) === $st)>
                                {{ strtoupper($st) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tiposConhecidos as $tipo)
                            <option value="{{ $tipo }}"
                                @selected(($filtros['tipo'] ?? null) === $tipo)>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.mensagens.por_cliente') }}" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- INFO DO CLIENTE SELECIONADO --}}
    @if(!empty($filtros['cliente_id']))
        @php
            $clienteSelecionado = $clientes->firstWhere('id', $filtros['cliente_id']);
        @endphp
        @if($clienteSelecionado)
            <div class="alert alert-info">
                <strong>Cliente:</strong> {{ $clienteSelecionado->nome }} (ID: {{ $clienteSelecionado->id }})
            </div>
        @endif
    @endif

    {{-- LISTAGEM DE MENSAGENS --}}
    <div class="card">
        <div class="card-header">
            Histórico de mensagens
        </div>
        <div class="card-body p-0">
            @if($mensagens->isEmpty())
                <p class="p-3 mb-0 text-muted">
                    Nenhuma mensagem encontrada para os filtros selecionados.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora envio</th>
                                <th>Status</th>
                                <th>Tipo</th>
                                <th>Campanha</th>
                                <th>Pedido</th>
                                <th>Conteúdo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mensagens as $msg)
                                <tr>
                                    <td>{{ $msg->id }}</td>
                                    <td>
                                        @if($msg->sent_at)
                                            {{ $msg->sent_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($msg->status) {
                                                'sent'   => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'queued' => 'bg-secondary',
                                                default  => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $msg->status ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $msg->tipo ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($msg->campanha)
                                            {{ $msg->campanha->nome }} ({{ $msg->campanha_id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($msg->pedido)
                                            #{{ $msg->pedido->id }}
                                        @elseif($msg->pedido_id)
                                            #{{ $msg->pedido_id }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span title="{{ $msg->conteudo }}">
                                            {{ Str::limit($msg->conteudo, 100) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $mensagens->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

Como isso se conecta ao resto do sistema

Toda vez que você chama MensageriaService::enviarWhatsapp(...), uma linha é criada na appmensagens com:

cliente_id

pedido_id (quando houver)

campanha_id (quando houver)

tipo (ex.: pedido_entregue_cliente, indicacao_primeira_compra)

status (sent ou failed)

sent_at (data/hora real do envio)

Os relatórios:

por campanha (porCampanha) olham principalmente para campanha_id + tipo + status + sent_at.

por cliente (porCliente) olham principalmente para cliente_id + tipo + status + sent_at.

Com isso, você já tem uma base forte pra:

Auditar qualquer envio;

Ter visão 360º de cliente (qual mensagem recebeu em qual etapa);

Medir efetividade das campanhas de indicação e outras campanhas que você criar.

