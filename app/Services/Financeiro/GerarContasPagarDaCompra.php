<?php

namespace App\Services\Financeiro;

use App\Models\PedidoCompra;
use App\Models\ContasPagar;
use App\Models\PlanoPagamento;
use Carbon\Carbon;

class GerarContasPagarDaCompra
{
    /**
     * Gera as parcelas de contas a pagar para uma compra.
     *
     * Regras:
     *  - Valor total da compra é dividido em N parcelas;
     *  - Data base para vencimento = data_emissao (entrega). Se não tiver, usa data_compra. Se ainda não tiver, hoje;
     *  - Se houver Plano de Pagamento:
     *      • Parcela 1 => data_base + prazo1 dias
     *      • Parcela 2 => data_base + prazo2 dias
     *      • Parcela 3 => data_base + prazo3 dias
     *      • Parcela >3 => continua somando (ex.: último prazo + 30 * (n-3));
     *  - Se NÃO houver Plano de Pagamento:
     *      • 1 parcela => vence na data base
     *      • 2+ parcelas => 1ª = +1 mês, 2ª = +2 meses, etc.
     */
    public function executar(PedidoCompra $compra): void
    {
        // Se já existem contas a pagar dessa compra, não gera de novo
        if ($compra->contasPagar()->exists()) {
            return;
        }

        // ---------------- VALOR BASE DA COMPRA ----------------
        $valorTotalBruto = (float) ($compra->valor_total ?? 0);
        $encargos        = (float) ($compra->encargos ?? 0);
        $valorLiquido    = (float) ($compra->valor_liquido ?? 0);

        // Regra:
        // 1) Se já existe valor_liquido (>0), usa ele (é o Total Líquido do index)
        // 2) Senão, tenta calcular como bruto + encargos
        if ($valorLiquido > 0) {
            $total = $valorLiquido;
        } else {
            $total = $valorTotalBruto + $encargos;
        }

        if ($total <= 0) {
            // Nada a gerar se valor total for 0
            return;
        }
        // ------------------------------------------------------

        // Tenta carregar o plano de pagamento (se houver)
        $plano = null;
        if ($compra->plano_pagamento_id) {
            $plano = PlanoPagamento::find($compra->plano_pagamento_id);
        }

        // Quantidade de parcelas:
        //  - Prioriza o que está no plano
        //  - Se não tiver, usa qt_parcelas da compra
        $totalParcelas = (int) ($plano->parcelas ?? $compra->qt_parcelas ?? 1);
        if ($totalParcelas < 1) {
            $totalParcelas = 1;
        }

        // Data base para vencimentos: usa data_emissao (entrega), se não tiver, data_compra, se não, hoje
        $dataBaseRaw = $compra->data_emissao ?: $compra->data_compra;
        if ($dataBaseRaw) {
            $dataBase = Carbon::parse($dataBaseRaw)->startOfDay();
        } else {
            $dataBase = Carbon::today()->startOfDay();
        }

        // Valor base por parcela (ajustando a última pra fechar certinho)
        $valorParcelaBase = round($total / $totalParcelas, 2);
        $valorAcumulado   = 0.0;

        for ($parcela = 1; $parcela <= $totalParcelas; $parcela++) {

            if ($parcela < $totalParcelas) {
                $valorParcela   = $valorParcelaBase;
                $valorAcumulado += $valorParcela;
            } else {
                // Última parcela = total - o que já foi somado (pra eliminar diferenças de centavos)
                $valorParcela = round($total - $valorAcumulado, 2);
            }

            // --------- REGRA DE VENCIMENTO ----------
            if ($plano) {
                // Se existe plano, usamos os prazos em DIAS
                $diasPrazo = 0;

                if ($parcela === 1) {
                    $diasPrazo = (int) ($plano->prazo1 ?? 0);
                } elseif ($parcela === 2) {
                    $diasPrazo = (int) ($plano->prazo2 ?? $plano->prazo1 ?? 0);
                } elseif ($parcela === 3) {
                    $diasPrazo = (int) ($plano->prazo3 ?? $plano->prazo2 ?? $plano->prazo1 ?? 0);
                } else {
                    // Para parcelas >3, faz uma progressão simples:
                    $basePrazo = (int) ($plano->prazo3 ?? $plano->prazo2 ?? $plano->prazo1 ?? 0);
                    $diasPrazo = $basePrazo + 30 * ($parcela - 3);
                }

                $dataVencimento = (clone $dataBase)->addDays($diasPrazo);
            } else {
                // Fallback: regra mensal
                if ($totalParcelas === 1) {
                    $dataVencimento = (clone $dataBase);
                } else {
                    $dataVencimento = (clone $dataBase)->addMonths($parcela);
                }
            }
            // -----------------------------------------

            ContasPagar::create([
                'compra_id'          => $compra->id,
                'numero_nota'        => $compra->numero_nota,
                'fornecedor_id'      => $compra->fornecedor_id,
                'parcela'            => $parcela,
                'total_parcelas'     => $totalParcelas,
                'forma_pagamento_id' => $compra->forma_pagamento_id ?? 1,
                'plano_pagamento_id' => $compra->plano_pagamento_id,
                'data_emissao'       => $dataBase,
                'data_vencimento'    => $dataVencimento,
                'valor'              => $valorParcela,
                'status'             => 'ABERTO',
            ]);
        }
    }
}
