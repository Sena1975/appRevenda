<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\PedidoCompra;
use App\Models\Produto;

class EstoqueService
{
    /**
     * 🔹 Registrar movimentação de entrada (compra confirmada)
     */
    public function registrarEntradaCompra(PedidoCompra $pedido)
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = $item->produto_id;
            $quantidade = $item->qtd_disponivel ?? $item->quantidade;

            if (!$produtoId || $quantidade <= 0) continue;

            // 🔹 Atualiza o estoque gerencial
            DB::table('appestoque')->updateOrInsert(
                ['produto_id' => $produtoId],
                ['codfabnumero'  => $item->produto->codfabnumero ?? null,
                    'estoque_gerencial' => DB::raw("estoque_gerencial + {$quantidade}"),
                    'reservado'         => 0,
                    'avaria'            => 0,
                    'updated_at'        => now()
                ]
            );

            // 🔹 Registra a movimentação de entrada
            DB::table('appmovestoque')->insert([
                'produto_id'    => $produtoId,
                'codfabnumero'  => $item->produto->codfabnumero ?? null,
                'tipo_mov'      => 'ENTRADA',
                'origem'        => 'COMPRA',
                'origem_id'     => $pedido->id,
                'data_mov'      => now(),
                'quantidade'    => $quantidade,
                'preco_unitario'=> $item->preco_unitario,
                // 'total'         => $quantidade * $item->preco_unitario,
                'observacao'    => 'Entrada por recebimento da compra',
                'status'        => 'CONFIRMADO',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /**
     * 🔹 Registrar movimentação de saída (venda)
     */
    public function registrarSaidaVenda($pedido)
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = $item->produto_id;
            $quantidade = $item->quantidade ?? 0;

            if (!$produtoId || $quantidade <= 0) continue;

            // 🔹 Atualiza o estoque gerencial (baixa)
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->decrement('estoque_gerencial', $quantidade);

            // 🔹 Registra a saída
            DB::table('appmovestoque')->insert([
                'produto_id'    => $produtoId,
                'codfabnumero'  => $item->produto->codfabnumero ?? null,
                'tipo_mov'      => 'SAIDA',
                'origem'        => 'VENDA',
                'origem_id'     => $pedido->id,
                'data_mov'      => now(),
                'quantidade'    => -$quantidade,
                'preco_unitario'=> $item->preco_unitario ?? 0,
                // 'total'         => -($quantidade * ($item->preco_unitario ?? 0)),
                'observacao'    => 'Saída por venda confirmada',
                'status'        => 'CONFIRMADO',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /**
     * 🔹 Registrar ajuste manual de estoque
     */
    public function ajusteManual($produtoId, $novoEstoque, $motivo = 'Ajuste manual')
    {
        $produto = Produto::find($produtoId);
        if (!$produto) return;

        $estoqueAtual = DB::table('appestoque')->where('produto_id', $produtoId)->first();

        $ajuste = $novoEstoque - ($estoqueAtual->estoque_gerencial ?? 0);
        if ($ajuste == 0) return;

        // 🔹 Atualiza o estoque
        DB::table('appestoque')
            ->where('produto_id', $produtoId)
            ->update(['estoque_gerencial' => $novoEstoque]);

        // 🔹 Registra a movimentação
        DB::table('appmovestoque')->insert([
            'produto_id'    => $produtoId,
            'codfabnumero'  => $produto->codfabnumero ?? null,
            'tipo_mov'      => $ajuste > 0 ? 'ENTRADA' : 'SAIDA',
            'origem'        => 'AJUSTE',
            'data_mov'      => now(),
            'quantidade'    => $ajuste,
            'preco_unitario'=> 0,
            'total'         => 0,
            'observacao'    => $motivo,
            'status'        => 'CONFIRMADO',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * 🔹 Estornar uma entrada de compra (cancelamento de pedido)
     */
    public function estornarEntradaCompra(PedidoCompra $pedido, $motivo = 'Cancelamento de pedido')
    {
        if (!$pedido || !$pedido->itens) return;

        foreach ($pedido->itens as $item) {
            $produtoId = $item->produto_id;
            $quantidade = $item->qtd_disponivel ?? $item->quantidade;

            if (!$produtoId || $quantidade <= 0) continue;

            // 🔹 Atualiza o estoque (baixa)
            DB::table('appestoque')
                ->where('produto_id', $produtoId)
                ->decrement('estoque_gerencial', $quantidade);

            // 🔹 Registra a movimentação de estorno (nova linha)
            DB::table('appmovestoque')->insert([
                'produto_id'    => $produtoId,
                'codfabnumero'  => $item->produto->codfabnumero ?? null,
                'tipo_mov'      => 'SAIDA',
                'origem'        => 'DEVOLUCAO',
                'origem_id'     => $pedido->id,
                'data_mov'      => now(),
                'quantidade'    => -$quantidade,
                'preco_unitario'=> $item->preco_unitario,
                'observacao'    => $motivo,
                'status'        => 'CONFIRMADO',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
