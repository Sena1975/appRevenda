<?php

namespace App\Observers;

use App\Models\PedidoVenda;
use App\Models\Campanha;
use App\Services\CampanhaService;
use App\Services\MensageriaService;
use App\Services\DisparadorCampanhaMensagemService;
use Illuminate\Support\Facades\Log;

class PedidoVendaObserver
{
    /**
     * Disparado quando o pedido é criado.
     * - Envia mensagem PARA O INDICADOR (se for campanha de indicação).
     * - Envia mensagem PARA O CLIENTE (resumo do pedido / previsão).
     */
    public function created(PedidoVenda $pedido): void
    {
        try {
            /** @var MensageriaService $mensageria */
            $mensageria = app(MensageriaService::class);

            /** @var DisparadorCampanhaMensagemService $disparador */
            $disparador = app(DisparadorCampanhaMensagemService::class);

            // Campanha vinculada (se existir)
            $campanha = $pedido->campanha_id
                ? Campanha::find($pedido->campanha_id)
                : null;

            // 1) INDICADOR: se for campanha de indicação + status PENDENTE
            if ($this->deveDispararIndicacao($pedido) && $pedido->status === 'PENDENTE') {

                $indicador = $pedido->indicador;
                $indicado  = $pedido->cliente;

                if ($indicador && $indicado && $campanha) {

                    // Contexto extra para placeholders do modelo
                    $contextoExtra = [
                        'nome_indicador' => $indicador->nome,
                        'nome_indicado'  => $indicado->nome,
                    ];

                    $msgModel = $disparador->dispararPorEvento(
                        evento: 'indicacao_pedido_pendente',
                        cliente: $indicador, // quem recebe é o INDICADOR
                        pedido: $pedido,
                        campanha: $campanha,
                        contextoExtra: $contextoExtra,
                    );

                    if ($msgModel) {
                        Log::info('Campanha indicação: msg PENDENTE registrada/enviada ao indicador', [
                            'pedido_id'    => $pedido->id,
                            'indicador_id' => $indicador->id,
                            'mensagem_id'  => $msgModel->id,
                            'msg_status'   => $msgModel->status,
                        ]);
                    } else {
                        Log::warning('Campanha indicação: nenhuma configuração de mensagem para evento indicacao_pedido_pendente', [
                            'pedido_id'    => $pedido->id,
                            'indicador_id' => $indicador->id,
                            'campanha_id'  => $campanha->id,
                        ]);
                    }
                }
            }

            // 2) CLIENTE: sempre que criar pedido envia resumo (mensagem "padrão")
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
                'pedido_id' => $pedido->id ?? null,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Disparado quando o pedido é atualizado.
     * Aqui tratamos APENAS a mensagem do INDICADOR na entrega.
     *
     * O RECIBO do CLIENTE continua sendo enviado pelo controller
     * (ex.: PedidoVendaController::confirmarEntrega()).
     */
    public function updated(PedidoVenda $pedido): void
    {
        try {
            // Só reage se o status mudou
            if (!$pedido->wasChanged('status')) {
                return;
            }

            // Nos interessa apenas quando mudou para ENTREGUE
            if ($pedido->status !== 'ENTREGUE') {
                return;
            }

            $cliente   = $pedido->cliente;
            $indicador = $pedido->indicador;

            if (!$cliente || !$indicador) {
                return;
            }

            // Só segue se for campanha de indicação
            if (!$this->deveDispararIndicacao($pedido)) {
                return;
            }

            /** @var DisparadorCampanhaMensagemService $disparador */
            $disparador = app(DisparadorCampanhaMensagemService::class);

            // Campanha vinculada (se existir)
            $campanha = $pedido->campanha_id
                ? Campanha::find($pedido->campanha_id)
                : null;

            if (!$campanha) {
                Log::warning('Pedido entregue com indicação, mas sem campanha vinculada válida', [
                    'pedido_id' => $pedido->id,
                ]);
                return;
            }

            // TODO: calcule aqui o valor real do prêmio, se já tiver regra
            $valorPremio = null;

            $contextoExtra = [
                'nome_indicador' => $indicador->nome,
                'nome_indicado'  => $cliente->nome,
            ];

            if (!is_null($valorPremio)) {
                // Envia já no formato desejado para o placeholder {{VALOR_PREMIO}}
                $contextoExtra['valor_premio'] = number_format((float) $valorPremio, 2, ',', '.');
            }

            // Dispara a mensagem para o INDICADOR
            $msgModel = $disparador->dispararPorEvento(
                evento: 'indicacao_premio_pix',
                cliente: $indicador,
                pedido: $pedido,
                campanha: $campanha,
                contextoExtra: $contextoExtra,
            );

            if ($msgModel) {
                Log::info('Campanha indicação: msg PRÊMIO registrada/enviada ao indicador', [
                    'pedido_id'    => $pedido->id,
                    'indicador_id' => $indicador->id,
                    'mensagem_id'  => $msgModel->id,
                    'msg_status'   => $msgModel->status,
                ]);
            } else {
                Log::warning('Campanha indicação: nenhuma configuração de mensagem para evento indicacao_premio_pix', [
                    'pedido_id'    => $pedido->id,
                    'indicador_id' => $indicador->id,
                    'campanha_id'  => $campanha->id,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('PedidoVendaObserver@updated erro', [
                'pedido_id' => $pedido->id ?? null,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Regra dinâmica usando CampanhaService + metodo_php.
     * Aqui validamos se o pedido está vinculado a uma campanha
     * com metodo_php = 'isCampanhaIndicacao' e em vigência.
     */
    private function deveDispararIndicacao(PedidoVenda $pedido): bool
    {
        // precisa ter indicador vinculado
        if (!$pedido->indicador_id) {
            return false;
        }

        // precisa ter campanha vinculada
        if (!$pedido->campanha_id) {
            return false;
        }

        /** @var CampanhaService $campanhaService */
        $campanhaService = app(CampanhaService::class);

        // appcampanha.metodo_php = "isCampanhaIndicacao"
        $metodoPhpIndicacao = 'isCampanhaIndicacao';

        $campanhasIndicacaoIds = $campanhaService
            ->campanhasVigentesIdsPorMetodo($metodoPhpIndicacao);

        Log::info('deveDispararIndicacao check', [
            'pedido_id'           => $pedido->id,
            'pedido_campanha_id'  => $pedido->campanha_id,
            'campanhas_indicacao' => $campanhasIndicacaoIds,
        ]);

        if (empty($campanhasIndicacaoIds)) {
            return false;
        }

        return in_array((int) $pedido->campanha_id, $campanhasIndicacaoIds, true);
    }

    /**
     * Texto enviado ao CLIENTE assim que o pedido é criado (PENDENTE).
     * Informa que estamos providenciando, mostra valor, forma de pagamento e previsão de entrega.
     * Aqui também dá pra incluir observação do pedido.
     */
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
}
