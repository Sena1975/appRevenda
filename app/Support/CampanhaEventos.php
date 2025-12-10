<?php

namespace App\Support;

class CampanhaEventos
{
    /**
     * Eventos padrão para campanhas de indicação.
     *
     * @return array<string,string> [evento => descrição]
     */
    public static function eventosIndicacao(): array
    {
        return [
            'indicacao_pedido_pendente'         => 'Indicação: pedido do indicado registrado',
            'indicacao_premio_pix'              => 'Indicação: prêmio em dinheiro disponível',
            'convite_indicacao_primeira_compra' => 'Convite para indicação após primeira compra',
        ];
    }
}
