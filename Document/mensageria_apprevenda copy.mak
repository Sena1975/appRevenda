# Mensageria e Integração WhatsApp (appRevenda)

## 1. Visão geral

A mensageria do appRevenda integra:

- **Sistema de Revenda** (Pedidos, Clientes, Campanhas)
- **BotConversa** (envio de mensagens WhatsApp)
- **Camada interna de mensageria**, responsável por:
  - Normalizar os envios
  - Registrar tudo na tabela `appmensagens`
  - Oferecer relatórios e rastreabilidade

### Objetivos

- Cadastrar e sincronizar clientes com o **BotConversa**.
- Enviar mensagens automáticas via WhatsApp em pontos chave da jornada:
  - Boas-vindas ao cliente novo
  - Resumo do pedido criado
  - Recibo de entrega
  - Campanha de indicação (aviso ao indicador e ao indicado)
  - **Convite para campanha de indicação 24h após a entrega da primeira compra**
- Manter **histórico detalhado** em banco:
  - Por cliente, pedido, campanha, tipo, status etc.
- Permitir criação de **relatórios e filtros** de mensagens.

---

## 2. Arquitetura da Mensageria

### 2.1 Tabela de log: `appmensagens`

**Tabela**: `appmensagens`  
**Objetivo**: registrar tudo que é enviado/recebido (especialmente WhatsApp), sempre que possível vinculado a:

- `cliente_id` (cliente)
- `pedido_id` (pedido de venda)
- `campanha_id` (campanha)

**Campos principais (conceito):**

- `id` (bigint, PK auto-increment)
- `cliente_id` (nullable, FK `appcliente`)
- `pedido_id` (nullable, FK `apppedidovenda`)
- `campanha_id` (nullable, FK `appcampanha`)
- `canal` (string)  
  - Ex.: `'whatsapp'`, `'sms'`, `'email'` (atualmente usamos `whatsapp`)
- `direcao` (string)  
  - `'outbound'` (enviado pelo sistema)  
  - `'inbound'` (recebido de fora)
- `tipo` (string)  
  - Tipo lógico interno:  
    - `boas_vindas_cliente`
    - `pedido_pendente_cliente`
    - `indicacao_pedido_pendente`
    - `indicacao_premio_pix`
    - `recibo_entrega_cliente`
    - `convite_campanha_indicacao_primeira_compra`
    - etc.
- `conteudo` (text)  
  - Texto efetivo da mensagem (WhatsApp).
- `payload` (json, nullable)  
  - Payload bruto/enriquecido (ex.: retorno do BotConversa).
- `provider` (string, nullable)  
  - Ex.: `'botconversa'`, no futuro `'z-api'`, `'twilio'` etc.
- `provider_subscriber_id` (string, nullable)  
  - ID do contato no provedor (assinante no BotConversa).
- `provider_message_id` (string, nullable)  
  - ID da mensagem no provedor.
- `provider_status` (string, nullable)  
  - Status informado pelo provedor (`queued`, `sent`, `delivered`, `failed`, `read` etc.)
- `status` (string)  
  - Status lógico no sistema: `queued`, `sent`, `delivered`, `failed`.
- `sent_at` (datetime, nullable)  
- `delivered_at` (datetime, nullable)  
- `failed_at` (datetime, nullable)  
- `created_at`, `updated_at` (padrão Laravel)

---

### 2.2 Model `Mensagem`

**Arquivo**: `app/Models/Mensagem.php`  
**Tabela**: `appmensagens`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
2.3 MensageriaService

Arquivo: app/Services/MensageriaService.php

Objetivo: ser a porta de entrada única para registrar/envio de mensagens via WhatsApp (ou outros canais no futuro).

Responsabilidades:

Criar registro em appmensagens.

Chamar o provedor (ex.: BotConversa) para envio.

Consolidar:

canal = 'whatsapp'

direcao = 'outbound'

tipo lógico (pedido_pendente_cliente, recibo_entrega_cliente, etc.)

vínculos com cliente/pedido/campanha.

Assinatura típica:

public function enviarWhatsapp(
    Cliente $cliente,
    string $conteudo,
    string $tipo,
    ?PedidoVenda $pedido = null,
    ?Campanha $campanha = null,
    array $payloadExtra = []
): Mensagem


Fluxo básico interno:

Cria registro em Mensagem (status inicial, canal, direcao, tipo, conteudo, vínculos).

Chama BotConversaService para enviar.

Atualiza status, sent_at, provider, provider_*.

3. Integração com BotConversa
3.1 Configuração (config/services.php)
'botconversa' => [
    'base_url'      => env('BOTCONVERSA_BASE_URL', 'https://app.botconversa.com.br/api/v1/'),
    'api_key'       => env('BOTCONVERSA_API_KEY'),
    'origin_tag_id' => env('BOTCONVERSA_ORIGIN_TAG_ID'), // tag opcional p/ origem "Sistema de Revenda"
],

3.2 Variáveis .env
BOTCONVERSA_BASE_URL="https://app.botconversa.com.br/api/v1/"
BOTCONVERSA_API_KEY="SUA_API_KEY_AQUI"
BOTCONVERSA_ORIGIN_TAG_ID=123456


BOTCONVERSA_API_KEY: chave fornecida pelo painel do BotConversa.

BOTCONVERSA_ORIGIN_TAG_ID (opcional): TAG usada para marcar contatos vindos do appRevenda.

3.3 BotConversaService

Arquivo: app/Services/Whatsapp/BotConversaService.php
Objetivo: encapsular as chamadas HTTP para a API do BotConversa.

Responsabilidades principais:

Buscar assinante por telefone.

Criar assinante (subscriber).

Enviar mensagens de texto.

Normalizar DDD/telefone para o formato internacional (55 + DDD + número).

Métodos principais (conceito)
public function findSubscriberByPhone(string $telefone): ?array;
public function createSubscriber(string $nome, string $telefone, ?string $tagId = null): ?array;
public function sendMessageToSubscriber(string $subscriberId, string $mensagem): bool;
public function enviarParaTelefone(string $telefoneBruto, string $mensagem, ?string $nome = null): bool;
public function enviarParaCliente(Cliente $cliente, string $mensagem): bool;


findSubscriberByPhone: faz GET subscriber/?phone=....

createSubscriber: POST subscriber/create/ com full_name, phone, tags.

sendMessageToSubscriber: POST subscriber/{id}/send_message/.

enviarParaTelefone:

normaliza telefone,

busca assinante,

cria se não existir,

envia mensagem.

enviarParaCliente:

extrai telefone de $cliente->telefone, $cliente->phone ou $cliente->whatsapp.

4. Templates e Fluxos de WhatsApp
4.1 MensagensCampanhaService

Arquivo: app/Services/Whatsapp/MensagensCampanhaService.php
Objetivo: centralizar templates de mensagens WhatsApp relacionadas a:

Campanhas (principalmente indicação)

Boas-vindas

Recibos e avisos de pedido

Convite pós-primeira compra

Responsabilidades:

Montar texto com emojis, interpolando dados de cliente, pedido e campanha.

Chamar o MensageriaService para registrar/enviar.

Definir tipos (tipo) das mensagens.

Exemplos de métodos (conceito)

enviarMensagemBoasVindas(Cliente $cliente)

enviarAvisoIndicadorPedidoPendente(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido)

enviarAvisoIndicadorPedidoEntregue(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido, float $valorPremio)

enviarReciboEntregaCliente(PedidoVenda $pedido)

montarMensagemPedidoPendente(...)

montarMensagemPremioDisponivel(...)

montarMensagemConviteIndicacaoPrimeiraCompra(...) (NOVO)

4.2 Mensagem de “Pedido Pendente” para o cliente

No PedidoVendaObserver@created, usamos o método privado:

private function mensagemClientePedidoPendente(PedidoVenda $pedido): string
{
    $cliente = $pedido->cliente;
    $nome    = $cliente?->nome ?: 'cliente';

    $dataPedido = optional($pedido->data_pedido)->format('d/m/Y');
    $previsao   = optional($pedido->previsao_entrega)->format('d/m/Y');

    $valor = number_format(
        (float)($pedido->valor_liquido ?? $pedido->valor_total ?? 0),
        2,
        ',',
        '.'
    );

    $formaPg = $pedido->forma?->nome
        ?? $pedido->forma?->descricao
        ?? 'a forma de pagamento selecionada';

    $planoPg = $pedido->plano?->nome
        ?? $pedido->plano?->descricao
        ?? null;

    $linhaPlano = $planoPg
        ? "\n💳 Plano de pagamento: *{$planoPg}*"
        : '';

    $linhaPrevisao = $previsao
        ? "\n📅 Previsão de entrega: *{$previsao}*"
        : '';

    $linhaObs = $pedido->observacao
        ? "\n📝 Observação: {$pedido->observacao}"
        : '';

    return "Olá {$nome}! 👋\n\n"
        . "Registramos o seu pedido *#{$pedido->id}* e já estamos providenciando os produtos que você solicitou. 🙌\n\n"
        . "🧾 Data do pedido: *{$dataPedido}*\n"
        . "💰 Valor do pedido: *R$ {$valor}*\n"
        . "💳 Forma de pagamento: *{$formaPg}*"
        . $linhaPlano
        . $linhaPrevisao
        . $linhaObs
        . "\n\nAssim que o pedido for entregue, você receberá uma confirmação por aqui. "
        . "Qualquer dúvida, é só responder esta mensagem. 🙂";
}


Essa mensagem é enviada com tipo = 'pedido_pendente_cliente'.

4.3 Mensagens da Campanha de Indicação
4.3.1 Aviso ao Indicador: pedido pendente

Enviado em PedidoVendaObserver@created:

Condições:

Pedido com indicador_id.

Pedido vinculado a campanha de indicação vigente (metodo_php = 'isCampanhaIndicacao').

Status do pedido = 'PENDENTE'.

Fluxo:

PedidoVendaObserver@created chama MensagensCampanhaService::montarMensagemPedidoPendente(...).

MensageriaService::enviarWhatsapp é chamado com:

tipo = 'indicacao_pedido_pendente'

cliente = indicador

pedido = pedido

campanha = campanha (se existir)

4.3.2 Aviso ao Indicador: prêmio disponível (pedido entregue)

Enviado em PedidoVendaObserver@updated:

Condições:

status do pedido mudou para 'ENTREGUE'.

Pedido é de campanha de indicação (deveDispararIndicacao).

Cliente e indicador existem.

Fluxo simplificado:

PedidoVendaObserver@updated detecta mudança de status para 'ENTREGUE'.

Usa CampanhaService::calcularPremioIndicacao($pedido) para obter o valor.

Chama MensagensCampanhaService::montarMensagemPremioDisponivel(...).

MensageriaService::enviarWhatsapp com:

tipo = 'indicacao_premio_pix'

cliente = indicador

pedido = pedido

campanha = campanha

4.4 Recibo de Entrega para o Cliente

Enviado normalmente na confirmação de entrega do pedido (controller), ou pode ser disparado pelo Observer dependendo da implementação.

tipo = 'recibo_entrega_cliente'

cliente = cliente do pedido

pedido = pedido

status do pedido = 'ENTREGUE'

Essa mensagem é a base para o disparo posterior do convite da campanha de indicação.

4.5 NOVO: Convite de Campanha de Indicação após a primeira compra
4.5.1 Template: montarMensagemConviteIndicacaoPrimeiraCompra

Adicionado em MensagensCampanhaService:

public function montarMensagemConviteIndicacaoPrimeiraCompra(
    Cliente $cliente,
    PedidoVenda $pedido,
    ?Campanha $campanha = null
): string {
    $nome = $cliente->nome ?? 'cliente';

    $valor = number_format(
        (float)($pedido->valor_liquido ?? $pedido->valor_total ?? 0),
        2,
        ',',
        '.'
    );

    $nomeCampanha = $campanha?->nome ?? 'nossa campanha de indicação';
    $linkRegulamento = $campanha?->link_regulamento ?? null;
    $linhaLink = $linkRegulamento
        ? "\n\n📄 Detalhes e regulamento: {$linkRegulamento}"
        : '';

    return "Olá {$nome}! 👋\n\n"
        . "Que bom ter você comigo! Seu primeiro pedido já foi entregue e espero que tenha gostado dos produtos. 💙\n\n"
        . "Agora quero te fazer um convite especial: participe de *{$nomeCampanha}*.\n\n"
        . "Funciona assim:\n"
        . "➡️ Você indica amigas, familiares ou colegas;\n"
        . "➡️ Quando elas fizerem a primeira compra, você ganha uma recompensa em dinheiro 💰 que pode chegar a *10%* do valor da compra.\n\n"
        . "Seu último pedido foi de *R$ {$valor}*, imagina quanto dá pra ganhar indicando algumas pessoas? 😉\n"
        . $linhaLink
        . "\n\nSe quiser participar, é só me chamar aqui e eu já te explico como começar a indicar. 🙌";
}


O envio dessa mensagem é feito por um command agendado, descrito a seguir.

5. Gatilhos de envio
5.1 ClienteObserver: mensagem de boas-vindas

Arquivo: app/Observers/ClienteObserver.php

Fluxo:

Disparado ao criar um Cliente.

Obtém telefone do cliente.

Verifica se já existe no BotConversa; se não, cria subscriber.

Envia mensagem de boas-vindas via MensagensCampanhaService::enviarMensagemBoasVindas.

Principais pontos:

Usa origem_cadastro para diferenciar clientes vindos do app ou de fora.

Pode adicionar TAG de origem usando origin_tag_id de config/services.php.

5.2 PedidoVendaObserver@created

Arquivo: app/Observers/PedidoVendaObserver.php

Responsável por:

INDICADOR: mensagem de indicação, pedido pendente.

CLIENTE: resumo do pedido pendente.

Trechos importantes (resumido):

public function created(PedidoVenda $pedido): void
{
    try {
        $mensageria = app(MensageriaService::class);
        $campanha = $pedido->campanha_id ? Campanha::find($pedido->campanha_id) : null;

        // 1) INDICADOR
        if ($this->deveDispararIndicacao($pedido) && $pedido->status === 'PENDENTE') {

            $indicador = $pedido->indicador;
            $indicado  = $pedido->cliente;

            if ($indicador && $indicado) {
                $msgCampanha = app(MensagensCampanhaService::class);

                $valorPremio = null; // opcional, se já quiser calcular aqui

                $textoIndicador = $msgCampanha
                    ->montarMensagemPedidoPendente($indicador, $indicado, $pedido, $valorPremio);

                $msgModel = $mensageria->enviarWhatsapp(
                    cliente: $indicador,
                    conteudo: $textoIndicador,
                    tipo: 'indicacao_pedido_pendente',
                    pedido: $pedido,
                    campanha: $campanha,
                    payloadExtra: [
                        'evento' => 'indicacao_pedido_pendente',
                    ],
                );

                Log::info('Campanha indicação: msg PENDENTE registrada/enviada ao indicador', [
                    'pedido_id'    => $pedido->id,
                    'indicador_id' => $indicador->id,
                    'mensagem_id'  => $msgModel->id,
                    'msg_status'   => $msgModel->status,
                ]);
            }
        }

        // 2) CLIENTE
        $cliente = $pedido->cliente;

        if ($cliente) {
            $textoCliente = $this->mensagemClientePedidoPendente($pedido);

            $msgModel = $mensageria->enviarWhatsapp(
                cliente: $cliente,
                conteudo: $textoCliente,
                tipo: 'pedido_pendente_cliente',
                pedido: $pedido,
                campanha: $campanha,
                payloadExtra: [
                    'evento' => 'pedido_pendente_cliente',
                ],
            );

            Log::info('Pedido pendente: mensagem registrada/enviada ao cliente', [
                'pedido_id'   => $pedido->id,
                'cliente_id'  => $cliente->id,
                'mensagem_id' => $msgModel->id,
                'msg_status'  => $msgModel->status,
            ]);
        }

    } catch (\Throwable $e) {
        Log::error('PedidoVendaObserver@created erro', [
            'pedido_id' => $pedido->id,
            'erro'      => $e->getMessage(),
        ]);
    }
}

5.3 PedidoVendaObserver@updated

Responsável por:

Ao mudar status do pedido para ENTREGUE:

Enviar mensagem de prêmio para o indicador (campanha de indicação).

O recibo do cliente normalmente sai pelo controller, mas pode ser ajustado.

Trecho chave:

public function updated(PedidoVenda $pedido): void
{
    try {
        if (!$pedido->wasChanged('status')) {
            return;
        }

        if ($pedido->status !== 'ENTREGUE') {
            return;
        }

        $cliente   = $pedido->cliente;
        $indicador = $pedido->indicador;

        if (!$cliente || !$indicador) {
            return;
        }

        if (!$this->deveDispararIndicacao($pedido)) {
            return;
        }

        $mensageria = app(MensageriaService::class);
        $msgCampanha = app(MensagensCampanhaService::class);

        $campanha = $pedido->campanha_id
            ? Campanha::find($pedido->campanha_id)
            : null;

        $valorPremio = null; // ou calcular via CampanhaService

        $textoIndicador = $msgCampanha
            ->montarMensagemPremioDisponivel($indicador, $cliente, $pedido, $valorPremio);

        $msgModel = $mensageria->enviarWhatsapp(
            cliente: $indicador,
            conteudo: $textoIndicador,
            tipo: 'indicacao_premio_pix',
            pedido: $pedido,
            campanha: $campanha,
            payloadExtra: [
                'evento' => 'indicacao_premio_disponivel',
            ],
        );

        Log::info('Campanha indicação: msg PRÊMIO registrada/enviada ao indicador', [
            'pedido_id'    => $pedido->id,
            'indicador_id' => $indicador->id,
            'mensagem_id'  => $msgModel->id,
            'msg_status'   => $msgModel->status,
        ]);

    } catch (\Throwable $e) {
        Log::error('PedidoVendaObserver@updated erro', [
            'pedido_id' => $pedido->id,
            'erro'      => $e->getMessage(),
        ]);
    }
}


A função deveDispararIndicacao usa o CampanhaService para conferir se o pedido está vinculado a campanha com metodo_php = 'isCampanhaIndicacao' e em vigência.

6. Convite pós-primeira compra (24h depois da entrega)
6.1 Regra de negócio

Enviar uma mensagem para o cliente:

Que comprou pela primeira vez (primeiro pedido com status ENTREGUE);

Que já recebeu o recibo de entrega (tipo = 'recibo_entrega_cliente');

Com pelo menos 24h de diferença desde o envio desse recibo;

Convidando a participar da Campanha de Indicação;

Apenas uma vez por cliente (não repetir convite).

6.2 Command: EnviarConviteIndicacaoPrimeiraCompra

Arquivo: app/Console/Commands/EnviarConviteIndicacaoPrimeiraCompra.php

Signature:

protected $signature = 'campanhas:convite-indicacao-primeira-compra';


Fluxo do command (resumo):

Busca campanhas vigentes com metodo_php = 'isCampanhaIndicacao' (via CampanhaService).

Busca em appmensagens as mensagens:

tipo = 'recibo_entrega_cliente'

status = 'sent'

sent_at <= now() - 24h

com cliente_id e pedido_id preenchidos

cujo pedido esteja com status = 'ENTREGUE'

Para cada mensagem de recibo:

Verifica se já existe mensagem com
tipo = 'convite_campanha_indicacao_primeira_compra' para aquele cliente.

Se sim, pula.

Conta quantos pedidos ENTREGUES o cliente possui:

Se count != 1, pula (não é primeira compra entregue).

Monta o texto com montarMensagemConviteIndicacaoPrimeiraCompra(...).

Envia via MensageriaService::enviarWhatsapp com:

tipo = 'convite_campanha_indicacao_primeira_compra'

cliente = cliente

pedido = pedido

campanha = campanha de indicação

payloadExtra['origem_msg_id'] = id do recibo

6.3 Scheduler (Laravel 11+)

Arquivo: routes/console.php

Adicionar:

use Illuminate\Support\Facades\Schedule;


E no final do arquivo, configurar o agendamento:

Schedule::command('campanhas:convite-indicacao-primeira-compra')->hourly();


Isso manda o Laravel rodar o comando 1 vez por hora.

O comando só envia convites para quem já está no critério (24h após recibo da primeira compra entregue).

6.4 Cron no servidor (VPS)

No crontab do servidor (ex.: crontab -e):

* * * * * cd /var/www/appRevenda && /usr/bin/php artisan schedule:run >> /dev/null 2>&1


O cron chama o schedule:run a cada minuto.

O Laravel identifica que o nosso comando é ->hourly() e dispara na hora certa.

7. Relatórios de Mensagens
7.1 Objetivo

Permitir análises como:

Quantidade de mensagens por:

Campanha

Tipo

Período

Status

Mensagens por cliente/pedido.

Mensagens com erro (status = failed).

7.2 RelatorioMensagensController

Rotas exemplo:

Route::get('relatorios/mensagens/por-campanha', [RelatorioMensagensController::class, 'porCampanha'])
    ->name('relatorios.mensagens.por_campanha');

Route::get('relatorios/mensagens', [RelatorioMensagensController::class, 'index'])
    ->name('relatorios.mensagens.index');


Filtros aceitos:

tipo

campanha_id

cliente_id

pedido_id

canal

direcao

status

data_de / data_ate (baseado em sent_at)

7.3 View: resources/views/relatorios/mensagens_por_campanha.blade.php

Essa view:

Mostra formulário de filtros (tipo, canal, status, direção, datas, campanha).

Traz:

Resumo por campanha

Resumo por tipo

Lista detalhada de mensagens

(Ver implementação completa já definida anteriormente.)

7.4 View: relatorios/mensagens/index.blade.php (resumo global)

Lista paginada de mensagens com filtros básicos.

Mostra: data envio, cliente, pedido, campanha, tipo, canal, status, trecho de conteúdo.

Baseada em:

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

8. Boas práticas da mensageria

Registrar tudo em appmensagens
Toda mensagem automática deve ter:

cliente_id (quando possível)

pedido_id (quando fizer sentido)

campanha_id (quando estiver ligada a campanha)

tipo bem definido

canal = 'whatsapp'

status + timestamps (sent_at, failed_at)

Templates centralizados
Não espalhar texto de WhatsApp em controllers/observers.
Em vez disso:

Centralizar no MensagensCampanhaService.

Métodos semânticos como:

enviarReciboEntregaCliente

enviarAvisoIndicadorPedidoPendente

montarMensagemConviteIndicacaoPrimeiraCompra

Preparado para múltiplos provedores
O Model Mensagem já possui campos para:

provider

provider_subscriber_id

provider_message_id

provider_status

Isso permite integrar com outros provedores no futuro (Z-API, Twilio, etc.).

Uso de filas (futuro)
Para alto volume:

Envolver o envio em Jobs do Laravel.

MensageriaService poderia disparar Job em vez de enviar síncrono.

Atualizar status e timestamps dentro do Job.

Logs e monitoramento

Logar erros de integração com BotConversa.

Logar situações como:

Cliente sem telefone

Campanha de indicação não encontrada

Falha ao enviar convite de indicação