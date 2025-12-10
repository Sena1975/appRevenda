<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaMensagem;
use App\Models\Cliente;
use App\Models\Mensagem;
use App\Models\PedidoVenda;

class DisparadorCampanhaMensagemService
{
    public function __construct(
        protected MensageriaService $mensageria
    ) {
    }

    /**
     * Dispara uma mensagem de campanha baseada em evento + campanha + modelo configurado.
     *
     * @param  string           $evento        Ex.: 'indicacao_pedido_pendente'
     * @param  Cliente          $cliente       Cliente que vai receber a mensagem
     * @param  PedidoVenda|null $pedido        Pedido relacionado (se houver)
     * @param  Campanha|null    $campanha      Campanha de contexto (se houver)
     * @param  array            $contextoExtra Dados extras para placeholders (ex.: ['valor_premio' => 50])
     *
     * @return Mensagem|null    Retorna o registro em appmensagens ou null se não disparar
     */
    public function dispararPorEvento(
        string $evento,
        Cliente $cliente,
        ?PedidoVenda $pedido = null,
        ?Campanha $campanha = null,
        array $contextoExtra = []
    ): ?Mensagem {
        // Se não tiver campanha, neste primeiro momento não disparamos
        if (! $campanha) {
            return null;
        }

        /** @var CampanhaMensagem|null $config */
        $config = CampanhaMensagem::with('modelo')
            ->where('campanha_id', $campanha->id)
            ->where('evento', $evento)
            ->where('ativo', true)
            ->first();

        if (! $config || ! $config->modelo) {
            // Nenhum modelo configurado para este evento/campanha
            return null;
        }

        // TODO: usar delay_minutos + fila no futuro.
        // Por enquanto, vamos enviar imediato.

        $template = $config->modelo->conteudo;

        $texto = $this->montarTexto(
            template: $template,
            cliente: $cliente,
            pedido: $pedido,
            campanha: $campanha,
            contextoExtra: $contextoExtra
        );

        // Tipo lógico da mensagem (para log/relatórios)
        $tipo = 'campanha_' . $evento;

        return $this->mensageria->enviarWhatsapp(
            cliente: $cliente,
            conteudo: $texto,
            tipo: $tipo,
            pedido: $pedido,
            campanha: $campanha,
            payloadExtra: [
                'origem'                 => 'campanha_evento',
                'evento'                 => $evento,
                'campanha_mensagem_id'   => $config->id,
                'mensagem_modelo_id'     => $config->mensagem_modelo_id,
            ] + $contextoExtra,
        );
    }

    /**
     * Substitui placeholders básicos no template com dados de cliente/pedido/campanha.
     */
    protected function montarTexto(
        string $template,
        Cliente $cliente,
        ?PedidoVenda $pedido,
        ?Campanha $campanha,
        array $contextoExtra = []
    ): string {
        $replace = [
            '{{NOME_CLIENTE}}'  => $cliente->nome ?? '',
            '{{NOME_CAMPANHA}}' => $campanha?->nome ?? '',
        ];

        if ($pedido) {
            $valorTotal = (float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0);
            $dataPedido = optional($pedido->data_pedido)->format('d/m/Y');
            $dataEntrega = optional($pedido->data_entrega)->format('d/m/Y');

            $replace['{{NUMERO_PEDIDO}}']  = (string) $pedido->id;
            $replace['{{VALOR_PEDIDO}}']   = number_format($valorTotal, 2, ',', '.');
            $replace['{{VALOR_LIQUIDO}}']  = number_format($valorTotal, 2, ',', '.');
            $replace['{{DATA_PEDIDO}}']    = $dataPedido ?: '';
            $replace['{{DATA_ENTREGA}}']   = $dataEntrega ?: '';
        }

        // Contexto extra: ex.: ['valor_premio' => 50]
        foreach ($contextoExtra as $chave => $valor) {
            $placeholder = '{{' . strtoupper($chave) . '}}';
            $replace[$placeholder] = (string) $valor;
        }

        // Aplica substituições
        $texto = strtr($template, $replace);

        return $texto;
    }
}
