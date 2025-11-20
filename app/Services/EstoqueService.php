<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\PedidoCompra;
use App\Models\Produto;

class EstoqueService
{
    /**
     * 🔹 Registrar movimentação de entrada (compra confirmada)
     *  - Atualiza estoque_gerencial
     *  - Atualiza ultimo_preco_compra em appestoque
     *  - Atualiza preco_compra em appproduto
     *  - Registra movimento em appmovestoque
     */
    public function registrarEntradaCompra(PedidoCompra $pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId  = (int)$item->produto_id;
            $quantidade = (float)($item->qtd_disponivel ?? $item->quantidade ?? 0);
            if ($produtoId <= 0 || $quantidade <= 0) continue;

            $codfab = $item->produto->codfabnumero ?? $item->codfabnumero ?? null;

            // Custo unitário: se tiver total_liquido (já com encargos rateados) usa ele
            $totalLinhaLiquido = (float)($item->total_liquido ?? 0);
            if ($totalLinhaLiquido > 0 && $quantidade > 0) {
                $custoUnitario = $totalLinhaLiquido / $quantidade;
            } else {
                $custoUnitario = (float)($item->preco_unitario ?? 0);
            }

            // Garante linha no estoque e soma
            DB::table('appestoque')->updateOrInsert(
                ['produto_id' => $produtoId],
                [
                    'codfabnumero'        => $codfab,
                    'estoque_gerencial'   => DB::raw("COALESCE(estoque_gerencial,0) + {$quantidade}"),
                    'reservado'           => DB::raw("COALESCE(reservado,0)"),
                    'avaria'              => DB::raw("COALESCE(avaria,0)"),
                    'ultimo_preco_compra' => $custoUnitario,
                    'updated_at'          => now(),
                    'created_at'          => now(),
                ]
            );

            // Atualiza preço de compra do produto
            DB::table('appproduto')
                ->where('id', $produtoId)
                ->update([
                    'preco_compra' => $custoUnitario,
                    'updated_at'   => now(),
                ]);

            // Movimentação de ENTRADA - COMPRA
            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'ENTRADA',
                'origem'         => 'COMPRA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => $quantidade,
                'preco_unitario' => $custoUnitario,
                'observacao'     => 'Entrada por recebimento da compra',
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * 🔹 Reserva estoque para um pedido de venda (status PENDENTE)
     *  - incrementa appestoque.reservado
     *  - NÃO altera estoque_gerencial
     *  - registra appmovestoque com status PENDENTE (SAIDA)
     */
    public function reservarVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = (int)$item->produto_id;
            $qtd       = (int)($item->quantidade ?? 0);
            if ($produtoId <= 0 || $qtd <= 0) continue;

            $codfab = $item->codfabnumero ?? ($item->produto->codfabnumero ?? null);

            // Garante linha no estoque
            $exists = DB::table('appestoque')->where('produto_id', $produtoId)->exists();
            if (!$exists) {
                DB::table('appestoque')->insert([
                    'produto_id'        => $produtoId,
                    'codfabnumero'      => $codfab,
                    'estoque_gerencial' => 0,
                    'reservado'         => 0,
                    'avaria'            => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            // Incrementa reserva
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->update([
                    'reservado'  => DB::raw("COALESCE(reservado,0) + {$qtd}"),
                    'updated_at' => now(),
                ]);

            // Registra "pré-saída" pendente
            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'SAIDA',
                'origem'         => 'VENDA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => -$qtd,
                'preco_unitario' => (float)($item->preco_unitario ?? 0),
                'observacao'     => 'Reserva de estoque (pedido pendente)',
                'status'         => 'PENDENTE',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * 🔹 Confirma a venda:
     *  - baixa estoque_gerencial
     *  - libera reservado
     *  - registra saída CONFIRMADA
     *  - marca reservas PENDENTES do pedido como CONFIRMADO
     */
    public function confirmarSaidaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = (int) $item->produto_id;
            $qtd       = (int) ($item->quantidade ?? 0);
            if ($produtoId <= 0 || $qtd <= 0) {
                continue;
            }

            $codfab    = $item->codfabnumero ?? ($item->produto->codfabnumero ?? null);
            $nomeProd  = $item->produto->nome ?? $codfab ?? ('ID ' . $produtoId);

            // 🔒 Busca o registro de estoque com LOCK (mesma transação da confirmação)
            $estq = DB::table('appestoque')
                ->lockForUpdate()
                ->where('produto_id', $produtoId)
                ->first();

            if (!$estq) {
                // Não existe linha de estoque → não deixa confirmar
                throw new \RuntimeException(
                    "Não há registro de estoque para {$nomeProd}. Não é possível confirmar a entrega."
                );
            }

            $estoqueAtual = (int) ($estq->estoque_gerencial ?? 0);

            // 🚫 Regra: não pode confirmar se não tiver estoque gerencial suficiente
            if ($estoqueAtual < $qtd) {
                throw new \RuntimeException(
                    "Estoque insuficiente para {$nomeProd} (disp: {$estoqueAtual}, necessário: {$qtd})."
                );
            }

            // ✅ Aqui já sabemos que tem estoque → podemos baixar sem medo
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->update([
                    'estoque_gerencial' => DB::raw("estoque_gerencial - {$qtd}"),
                    'reservado'         => DB::raw("GREATEST(COALESCE(reservado,0) - {$qtd}, 0)"),
                    'updated_at'        => now(),
                ]);

            // Registra saída CONFIRMADA
            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'SAIDA',
                'origem'         => 'VENDA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => -$qtd,
                'preco_unitario' => (float) ($item->preco_unitario ?? 0),
                'observacao'     => 'Baixa de estoque por venda confirmada',
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Movimentos de reserva PENDENTES → CONFIRMADO
        DB::table('appmovestoque')
            ->where('origem', 'VENDA')
            ->where('origem_id', $pedido->id)
            ->where('status', 'PENDENTE')
            ->update([
                'status'     => 'CONFIRMADO',
                'updated_at' => now(),
            ]);
    }

    /**
     * 🔹 Cancelamento do pedido PENDENTE: libera reserva e marca movimentos PENDENTES como CANCELADO
     */
    public function cancelarReservaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = (int)$item->produto_id;
            $qtd       = (int)($item->quantidade ?? 0);
            if ($produtoId <= 0 || $qtd <= 0) continue;

            $codfab = $item->codfabnumero ?? ($item->produto->codfabnumero ?? null);

            // 1) Libera a reserva no saldo (reservado -= qtd)
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->update([
                    'reservado'  => DB::raw("GREATEST(COALESCE(reservado,0) - {$qtd}, 0)"),
                    'updated_at' => now(),
                ]);

            // 2) Insere uma movimentação de "retorno da reserva"
            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'ENTRADA',
                'origem'         => 'VENDA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => $qtd,
                'preco_unitario' => (float)($item->preco_unitario ?? 0),
                'observacao'     => 'Estorno de reserva (pedido cancelado)',
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // 3) Marcar as "reservas" PENDENTES desse pedido como CANCELADO (histórico)
        DB::table('appmovestoque')
            ->where('origem', 'VENDA')
            ->where('origem_id', $pedido->id)
            ->where('status', 'PENDENTE')
            ->update([
                'status'     => 'CANCELADO',
                'updated_at' => now(),
            ]);
    }

    /**
     * 🔹 Registrar movimentação de saída (venda direta - legado)
     *    *Usar confirmarSaidaVenda para fluxo com reserva.*
     */
    public function registrarSaidaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId  = (int)$item->produto_id;
            $quantidade = (int)($item->quantidade ?? 0);
            if ($produtoId <= 0 || $quantidade <= 0) continue;

            $codfab = $item->codfabnumero ?? ($item->produto->codfabnumero ?? null);

            // Garante linha no estoque
            $existe = DB::table('appestoque')->where('produto_id', $produtoId)->exists();
            if (!$existe) {
                DB::table('appestoque')->insert([
                    'produto_id'        => $produtoId,
                    'codfabnumero'      => $codfab,
                    'estoque_gerencial' => 0,
                    'reservado'         => 0,
                    'avaria'            => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            // Baixa o estoque gerencial (sem reserva)
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->update([
                    'estoque_gerencial' => DB::raw("GREATEST(COALESCE(estoque_gerencial,0) - {$quantidade}, 0)"),
                    'updated_at'        => now(),
                ]);

            // Registra saída
            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'SAIDA',
                'origem'         => 'VENDA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => -$quantidade,
                'preco_unitario' => (float)($item->preco_unitario ?? 0),
                'observacao'     => 'Saída por venda confirmada (fluxo direto)',
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * 🔹 Ajuste manual de estoque por delta (positivo/negativo)
     */
    public function registrarMovimentoManual(int $produtoId, string $tipoMov, float $quantidade, float $precoUnit = 0, string $observacao = 'Ajuste manual'): void
    {
        if ($produtoId <= 0 || $quantidade <= 0) return;

        $tipo = strtoupper($tipoMov);
        if (!in_array($tipo, ['ENTRADA','SAIDA','AJUSTE'])) {
            $tipo = 'AJUSTE';
        }

        $produto = Produto::find($produtoId);
        $codfab  = $produto->codfabnumero ?? null;

        $delta = $quantidade;
        if ($tipo === 'SAIDA') $delta = -$quantidade;

        // Garante linha no estoque
        $existe = DB::table('appestoque')->where('produto_id', $produtoId)->exists();
        if (!$existe) {
            DB::table('appestoque')->insert([
                'produto_id'        => $produtoId,
                'codfabnumero'      => $codfab,
                'estoque_gerencial' => 0,
                'reservado'         => 0,
                'avaria'            => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Aplica delta
        DB::table('appestoque')
            ->where('produto_id', $produtoId)
            ->update([
                'estoque_gerencial' => DB::raw("GREATEST(COALESCE(estoque_gerencial,0) + ({$delta}), 0)"),
                'updated_at'        => now(),
            ]);

        // Registra movimento (classifica ajuste como entrada/saída pelo sinal)
        $tipoRegistro = $tipo === 'AJUSTE'
            ? ($delta >= 0 ? 'ENTRADA' : 'SAIDA')
            : $tipo;

        DB::table('appmovestoque')->insert([
            'produto_id'     => $produtoId,
            'codfabnumero'   => $codfab,
            'tipo_mov'       => $tipoRegistro,
            'origem'         => 'AJUSTE',
            'origem_id'      => null,
            'data_mov'       => now(),
            'quantidade'     => $delta,
            'preco_unitario' => $precoUnit ?? 0,
            'observacao'     => $observacao ?: 'Ajuste manual',
            'status'         => 'CONFIRMADO',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * 🔹 Ajuste manual definindo estoque final
     */
    public function ajusteManual($produtoId, $novoEstoque, $motivo = 'Ajuste manual'): void
    {
        $produto = Produto::find($produtoId);
        if (!$produto) return;

        $estoqueAtual = DB::table('appestoque')->where('produto_id', $produtoId)->first();
        $ajuste = (float)$novoEstoque - (float)($estoqueAtual->estoque_gerencial ?? 0);
        if ($ajuste == 0.0) return;

        DB::table('appestoque')->updateOrInsert(
            ['produto_id' => $produtoId],
            [
                'codfabnumero'      => $produto->codfabnumero ?? null,
                'estoque_gerencial' => (float)$novoEstoque,
                'reservado'         => DB::raw("COALESCE(reservado,0)"),
                'avaria'            => DB::raw("COALESCE(avaria,0)"),
                'updated_at'        => now(),
                'created_at'        => now(),
            ]
        );

        DB::table('appmovestoque')->insert([
            'produto_id'     => $produtoId,
            'codfabnumero'   => $produto->codfabnumero ?? null,
            'tipo_mov'       => $ajuste >= 0 ? 'ENTRADA' : 'SAIDA',
            'origem'         => 'AJUSTE',
            'origem_id'      => null,
            'data_mov'       => now(),
            'quantidade'     => $ajuste,
            'preco_unitario' => 0,
            'observacao'     => $motivo,
            'status'         => 'CONFIRMADO',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * 🔹 Estornar uma entrada de compra (cancelamento)
     */
    public function estornarEntradaCompra(PedidoCompra $pedido, $motivo = 'Cancelamento de pedido'): void
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId  = (int)$item->produto_id;
            $quantidade = (float)($item->qtd_disponivel ?? $item->quantidade ?? 0);
            if ($produtoId <= 0 || $quantidade <= 0) continue;

            $codfab = $item->produto->codfabnumero ?? $item->codfabnumero ?? null;

            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->update([
                    'estoque_gerencial' => DB::raw("GREATEST(COALESCE(estoque_gerencial,0) - {$quantidade}, 0)"),
                    'updated_at'        => now(),
                ]);

            DB::table('appmovestoque')->insert([
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'SAIDA',
                'origem'         => 'DEVOLUCAO',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => -$quantidade,
                'preco_unitario' => (float)($item->preco_unitario ?? 0),
                'observacao'     => $motivo,
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
