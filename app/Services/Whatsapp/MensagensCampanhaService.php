<?php

namespace App\Services\Whatsapp;

use App\Models\Campanha;
use App\Models\Cliente;
use App\Models\Mensagem;
use App\Models\PedidoVenda;

use Illuminate\Support\Facades\Log;

class MensagensCampanhaService
{
    /**
     * Quando o indicado fez um pedido (status PENDENTE),
     * montamos a mensagem para avisar o INDICADOR
     * que ele terá um prêmio em dinheiro após a entrega.
     *
     * $valorPremio é opcional: se você já tiver a regra de cálculo,
     * pode passar aqui para exibir o valor exato.
     */
    public function montarMensagemPedidoPendente(
        Cliente $indicador,
        Cliente $indicado,
        PedidoVenda $pedido,
        ?float $valorPremio = null
    ): string {
        $nomeIndicador = $indicador->nome ?: 'cliente';
        $nomeIndicado  = $indicado->nome ?: 'seu indicado';
        $valorPedido   = $this->formatarValor((float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0));
        $dataPedido    = optional($pedido->data_pedido)->format('d/m/Y');

        $linhaPremio = $valorPremio !== null
            ? "Assim que o pedido for *entregue*, você terá direito a um prêmio de *R$ " . $this->formatarValor($valorPremio) . "* pela indicação. 💸\n\n"
            : "Assim que o pedido for *entregue*, você terá direito a um *prêmio em dinheiro* pela indicação. 💸\n\n";

        return "Olá {$nomeIndicador}! 👋\n\n"
            . "Tem novidade boa pra você! 🎉\n\n"
            . "{$nomeIndicado} acabou de fazer um pedido usando a sua indicação.\n"
            . "🧾 Pedido: *#{$pedido->id}*\n"
            . "📅 Data do pedido: *{$dataPedido}*\n"
            . "💰 Valor do pedido: *R$ {$valorPedido}*\n\n"
            . $linhaPremio
            . "Quando a entrega for concluída, te aviso por aqui com as instruções pra receber o prêmio. 😉";
    }

    /**
     * Quando o pedido do indicado for ENTREGUE,
     * montamos a mensagem para avisar o INDICADOR
     * e pedir a chave PIX.
     */
    public function montarMensagemPremioDisponivel(
        Cliente $indicador,
        Cliente $indicado,
        PedidoVenda $pedido,
        ?float $valorPremio = null
    ): string {
        $nomeIndicador = $indicador->nome ?: 'cliente';
        $nomeIndicado  = $indicado->nome ?: 'seu indicado';
        $valorPedido   = $this->formatarValor((float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0));
        $dataEntrega   = optional($pedido->previsao_entrega ?? $pedido->criado_em)->format('d/m/Y');

        $linhaPremio = $valorPremio !== null
            ? "Conforme a nossa campanha de indicação, você tem direito a um prêmio de *R$ " . $this->formatarValor($valorPremio) . "* 🎉\n\n"
            : "Conforme a nossa campanha de indicação, você tem direito a um *prêmio em dinheiro* 🎉\n\n";

        return "Olá {$nomeIndicador}! 👋\n\n"
            . "Boas notícias! ✅\n\n"
            . "O pedido do seu indicado *{$nomeIndicado}* já foi marcado como *ENTREGUE*.\n\n"
            . "🧾 Pedido: *#{$pedido->id}*\n"
            . "💰 Valor do pedido: *R$ {$valorPedido}*\n"
            . "📅 Data da entrega: *{$dataEntrega}*\n\n"
            . $linhaPremio
            . "Por favor, responda esta mensagem informando a sua *chave PIX* "
            . "(CPF, CNPJ, e-mail, telefone ou chave aleatória) para fazermos o pagamento do prêmio. 🙏";
    }

    /**
     * Convite para participar da campanha de indicação,
     * enviado 24h depois da confirmação de entrega
     * da PRIMEIRA COMPRA do cliente.
     */
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

        $nomeCampanha = $campanha?->nome ?? $campanha;
        $linkRegulamento = $campanha?->link_regulamento ?? null; // se tiver esse campo
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

    private function formatarValor(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
