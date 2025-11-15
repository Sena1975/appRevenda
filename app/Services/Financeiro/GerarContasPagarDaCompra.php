<?php

namespace App\Services\Financeiro;

use App\Models\PedidoCompra;
use App\Models\ContasPagar;
use Carbon\Carbon;

class GerarContasPagarDaCompra
{
    /**
     * Gera as parcelas de contas a pagar para uma compra.
     * Regra:
     *  - Divide o valor_total em N parcelas.
     *  - 1 parcela => vence na data base.
     *  - 2+ parcelas => 1ª vence no mês seguinte, demais mês a mês.
     */
    public function executar(PedidoCompra $compra): void
    {
        // Se já existem contas a pagar dessa compra, não gera de novo
        if ($compra->contasPagar()->exists()) {
            return;
        }

        $total = (float) ($compra->valor_total ?? 0);

        if ($total <= 0) {
            // Nada a gerar se valor total for 0
            return;
        }

        $totalParcelas = (int) ($compra->qt_parcelas ?: 1);

        // Data base para vencimentos: usa data_emissao, se não tiver, data_compra, se não, hoje
        $dataBase = $compra->data_emissao ?? $compra->data_compra;

        if ($dataBase) {
            $dataBase = Carbon::parse($dataBase);
        } else {
            $dataBase = Carbon::today();
        }

        // Valor base por parcela (ajustando a última pra fechar certinho)
        $valorParcelaBase = round($total / $totalParcelas, 2);
        $valorAcumulado   = 0;

        for ($parcela = 1; $parcela <= $totalParcelas; $parcela++) {

            if ($parcela < $totalParcelas) {
                $valorParcela   = $valorParcelaBase;
                $valorAcumulado += $valorParcela;
            } else {
                // Última parcela = total - o que já foi somado (pra não sobrar 1 ou 2 centavos)
                $valorParcela = round($total - $valorAcumulado, 2);
            }

            // --------- REGRA DE VENCIMENTO ----------
            // 1 parcela => vence na data base
            // 2+ parcelas => 1ª = +1 mês, 2ª = +2, etc.
            if ($totalParcelas === 1) {
                $dataVencimento = (clone $dataBase);
            } else {
                $dataVencimento = (clone $dataBase)->addMonths($parcela);
            }
            // -----------------------------------------

            ContasPagar::create([
                'compra_id'          => $compra->id,
                'numero_nota'        => $compra->numero_nota,
                'fornecedor_id'      => $compra->fornecedor_id,
                'parcela'            => $parcela,
                'total_parcelas'     => $totalParcelas,
                // agora usa o campo da compra, se existir
                'forma_pagamento_id' => $compra->forma_pagamento_id ?? 1,
                'data_emissao'       => $dataBase,
                'data_vencimento'    => $dataVencimento,
                'valor'              => $valorParcela,
                'status'             => 'ABERTO',
            ]);
        }
    }
}
