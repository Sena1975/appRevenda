<?php

namespace App\Console\Commands;

use App\Models\Mensagem;
use App\Models\PedidoVenda;
use App\Models\Campanha;
use App\Services\CampanhaService;
use App\Services\MensageriaService;
use App\Services\Whatsapp\MensagensCampanhaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarConviteIndicacaoPrimeiraCompra extends Command
{
    /**
     * Nome do comando para usar no scheduler.
     *
     * Ex.: php artisan campanhas:convite-indicacao-primeira-compra
     */
    protected $signature = 'campanhas:convite-indicacao-primeira-compra';

    protected $description = 'Envia convite para campanha de indicação 24h após a entrega do primeiro pedido do cliente.';

    public function handle(): int
    {
        $agora = now();
        $limite = $agora->copy()->subDay(); // 24 horas atrás

        Log::info('Iniciando envio de convites de indicação (primeira compra)', [
            'agora'   => $agora->toDateTimeString(),
            'limite'  => $limite->toDateTimeString(),
        ]);

        /** @var MensageriaService $mensageria */
        $mensageria = app(MensageriaService::class);

        /** @var MensagensCampanhaService $msgCampanha */
        $msgCampanha = app(MensagensCampanhaService::class);

        /** @var CampanhaService $campanhaService */
        $campanhaService = app(CampanhaService::class);

        // Pega campanhas de indicação vigentes
        $campanhasIndicacaoIds = $campanhaService
            ->campanhasVigentesIdsPorMetodo('isCampanhaIndicacao');

        if (empty($campanhasIndicacaoIds)) {
            Log::info('Nenhuma campanha de indicação vigente encontrada. Encerrando comando.');
            return Command::SUCCESS;
        }

        $campanhaIndicacaoId = (int) reset($campanhasIndicacaoIds);
        $campanhaIndicacao   = Campanha::find($campanhaIndicacaoId);

        // 1) Buscar mensagens de RECIBO DE ENTREGA enviadas há pelo menos 24h
        //    tipo: 'recibo_entrega_cliente' (ajuste se você usa outro tipo)
        Mensagem::query()
            ->where('tipo', 'recibo_entrega_cliente')
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $limite)
            // Tem que estar ligado a cliente + pedido
            ->whereNotNull('cliente_id')
            ->whereNotNull('pedido_id')
            // Pedido precisa estar ENTREGUE
            ->whereHas('pedido', function ($q) {
                $q->where('status', 'ENTREGUE');
            })
            // Vamos processar em blocos
            ->orderBy('id')
            ->chunkById(100, function ($mensagens) use ($mensageria, $msgCampanha, $campanhaIndicacao, $campanhaIndicacaoId) {

                foreach ($mensagens as $msgRecibo) {
                    $cliente = $msgRecibo->cliente;
                    $pedido  = $msgRecibo->pedido;

                    if (!$cliente || !$pedido) {
                        continue;
                    }

                    // 2) Verificar se JÁ FOI enviado convite antes para este cliente
                    $jaEnviouConvite = Mensagem::query()
                        ->where('cliente_id', $cliente->id)
                        ->where('tipo', 'convite_campanha_indicacao_primeira_compra')
                        ->exists();

                    if ($jaEnviouConvite) {
                        continue;
                    }

                    // 3) Verificar se é a PRIMEIRA COMPRA ENTREGUE do cliente
                    $qtdPedidosEntregues = PedidoVenda::query()
                        ->where('cliente_id', $cliente->id)
                        ->where('status', 'ENTREGUE')
                        ->count();

                    if ($qtdPedidosEntregues !== 1) {
                        // Se já tem mais de um pedido entregue, não envia
                        continue;
                    }

                    // 4) Montar texto do convite
                    $textoConvite = $msgCampanha
                        ->montarMensagemConviteIndicacaoPrimeiraCompra($cliente, $pedido, $campanhaIndicacao);

                    // 5) Enviar via WhatsApp e registrar
                    try {
                        $msgModel = $mensageria->enviarWhatsapp(
                            cliente: $cliente,
                            conteudo: $textoConvite,
                            tipo: 'convite_campanha_indicacao_primeira_compra',
                            pedido: $pedido,
                            campanha: $campanhaIndicacao,
                            payloadExtra: [
                                'evento'       => 'convite_campanha_indicacao_primeira_compra',
                                'origem_msg_id'=> $msgRecibo->id, // referência ao recibo original
                            ],
                        );

                        Log::info('Convite campanha indicação enviado (primeira compra)', [
                            'cliente_id'      => $cliente->id,
                            'pedido_id'       => $pedido->id,
                            'mensagem_id'     => $msgModel->id,
                            'msg_status'      => $msgModel->status,
                            'recibo_msg_id'   => $msgRecibo->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Erro ao enviar convite de indicação (primeira compra)', [
                            'cliente_id'    => $cliente->id,
                            'pedido_id'     => $pedido->id,
                            'recibo_msg_id' => $msgRecibo->id,
                            'erro'          => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Finalizou envio de convites de indicação (primeira compra).');

        return Command::SUCCESS;
    }
}
