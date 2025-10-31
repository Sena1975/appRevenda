<?php

namespace App\Services;

use App\Models\PedidoVenda;
use App\Models\PlanoPagamento;
use App\Models\ContasReceber;
use Carbon\Carbon;

class ContasReceberService
{
    /**
     * Gera parcelas para um pedido (apaga ABERTAS existentes antes).
     */
    public function gerarParaPedido(PedidoVenda $pedido): void
    {
        if (!$pedido->forma_pagamento_id || !$pedido->plano_pagamento_id || (float) $pedido->valor_liquido <= 0) {
            return;
        }

        $plano = PlanoPagamento::find($pedido->plano_pagamento_id);
        if (!$plano) return;

        // Remove parcelas em aberto deste pedido (caso já existam)
        ContasReceber::where('pedido_id', $pedido->id)
            ->where('status', 'ABERTO')
            ->delete();

        $parcelas  = (int)($plano->parcelas ?? 1);
        if ($parcelas < 1) $parcelas = 1;

        $valorBase = (float) $pedido->valor_liquido;
        $valorParc = round($valorBase / $parcelas, 2);
        $acum      = 0.00;
        $emissao   = Carbon::now();

        for ($i = 1; $i <= $parcelas; $i++) {
            // prazos (suporte padrão 3; ajuste se tiver mais)
            $diasPrazo = 0;
            if ($i === 1)      $diasPrazo = (int)($plano->prazo1 ?? 0);
            elseif ($i === 2)  $diasPrazo = (int)($plano->prazo2 ?? 0);
            elseif ($i === 3)  $diasPrazo = (int)($plano->prazo3 ?? 0);

            $venc  = (clone $emissao)->startOfDay()->addDays($diasPrazo);
            $valor = ($i < $parcelas) ? $valorParc : round($valorBase - $acum, 2);
            $acum += $valor;

            ContasReceber::create([
                'pedido_id'          => $pedido->id,
                'cliente_id'         => $pedido->cliente_id,
                'revendedora_id'     => $pedido->revendedora_id,
                'forma_pagamento_id' => $pedido->forma_pagamento_id,
                'parcela'            => $i,
                'total_parcelas'     => $parcelas,
                'data_emissao'       => $emissao,
                'data_vencimento'    => $venc,
                'valor'              => $valor,
                'status'             => 'ABERTO',
                'observacao'         => "Parcela {$i}/{$parcelas} - Pedido {$pedido->id} - Plano {$pedido->codplano}",
            ]);
        }
    }

    /**
     * Recalcula/gera novamente (apaga ABERTAS e recria).
     */
    public function recalcularParaPedido(PedidoVenda $pedido): void
    {
        $this->gerarParaPedido($pedido);
    }

    /**
     * Cancela parcelas em aberto de um pedido (não apaga baixas).
     */
    public function cancelarAbertasPorPedido(int $pedidoId): void
    {
        ContasReceber::where('pedido_id', $pedidoId)
            ->where('status', 'ABERTO')
            ->update([
                'status'     => 'CANCELADO',
                'atualizado_em' => now(),
            ]);
    }
}
