<?php

namespace App\Services\Whatsapp;

use App\Models\Cliente;
use App\Models\PedidoVenda;

class MensagensCampanhaService
{
    public function __construct(
        private BotConversaService $botConversa
    ) {}

    /**
     * Quando o indicado fez um pedido (status PENDENTE),
     * avisar o indicador que ele terá um prêmio em dinheiro após a entrega.
     */
    public function enviarAvisoIndicadorPedidoPendente(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido): bool
    {
        $mensagem = $this->montarMensagemPedidoPendente($indicador, $indicado, $pedido);

        return $this->botConversa->enviarParaCliente($indicador, $mensagem);
    }

    /**
     * Quando o pedido do indicado for ENTREGUE,
     * avisar o indicador e pedir a chave PIX.
     */
    public function enviarAvisoIndicadorPremioDisponivel(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido): bool
    {
        $mensagem = $this->montarMensagemPremioDisponivel($indicador, $indicado, $pedido);

        return $this->botConversa->enviarParaCliente($indicador, $mensagem);
    }

    /* =======================
        Montagem dos textos
       ======================= */

    private function montarMensagemPedidoPendente(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido): string
    {
        $nomeIndicador = $indicador->nome ?: 'cliente';
        $nomeIndicado  = $indicado->nome ?: 'seu indicado';
        $valorPedido   = $this->formatarValor((float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0));
        $dataPedido    = optional($pedido->data_pedido)->format('d/m/Y');

        return "Olá {$nomeIndicador}! 👋\n\n"
             . "Tem novidade boa pra você! 🎉\n\n"
             . "{$nomeIndicado} acabou de fazer um pedido usando a sua indicação.\n"
             . "🧾 Pedido: *#{$pedido->id}*\n"
             . "📅 Data do pedido: *{$dataPedido}*\n"
             . "💰 Valor do pedido: *R$ {$valorPedido}*\n\n"
             . "Assim que o pedido for *entregue*, você terá direito a um *prêmio em dinheiro* pela indicação. 💸\n\n"
             . "Quando a entrega for concluída, te aviso por aqui com as instruções pra receber o prêmio. 😉";
    }

    private function montarMensagemPremioDisponivel(Cliente $indicador, Cliente $indicado, PedidoVenda $pedido): string
    {
        $nomeIndicador = $indicador->nome ?: 'cliente';
        $nomeIndicado  = $indicado->nome ?: 'seu indicado';
        $valorPedido   = $this->formatarValor((float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0));
        $dataEntrega   = optional($pedido->previsao_entrega ?? $pedido->criado_em)->format('d/m/Y');

        return "Olá {$nomeIndicador}! 👋\n\n"
             . "Boas notícias! ✅\n\n"
             . "O pedido do seu indicado *{$nomeIndicado}* já foi marcado como *ENTREGUE*.\n\n"
             . "🧾 Pedido: *#{$pedido->id}*\n"
             . "💰 Valor do pedido: *R$ {$valorPedido}*\n"
             . "📅 Data da entrega: *{$dataEntrega}*\n\n"
             . "Conforme a nossa campanha de indicação, você tem direito a um *prêmio em dinheiro* 🎉\n\n"
             . "Por favor, responda esta mensagem informando a sua *chave PIX* "
             . "(CPF, CNPJ, e-mail, telefone ou chave aleatória) para fazermos o pagamento do prêmio. 🙏";
    }

    private function formatarValor(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}