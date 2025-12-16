<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoCompra;
use App\Models\Produto;

class EstoqueService
{
    /**
     * Resolve empresa_id de forma segura.
     * Prioridade:
     * 1) parâmetro explícito
     * 2) objeto (pedido) com empresa_id
     * 3) app('empresa') (middleware EmpresaAtiva)
     * 4) usuário logado
     */
    private function empresaIdOrFail(?int $empresaId = null, $obj = null): int
    {
        $id = (int)($empresaId ?? 0);

        if ($id <= 0 && $obj && isset($obj->empresa_id)) {
            $id = (int)($obj->empresa_id ?? 0);
        }

        if ($id <= 0 && app()->bound('empresa')) {
            $id = (int)(app('empresa')->id ?? 0);
        }

        if ($id <= 0) {
            $u = Auth::user();
            $id = (int)($u?->empresa_id ?? 0);
        }

        if ($id <= 0) {
            throw new \RuntimeException('Empresa ativa não definida para movimentação de estoque.');
        }

        return $id;
    }

    /**
     * Total líquido do item (considera preco_total / preco_unitario*qtde e abate desconto).
     */
    private function totalLiquidoItem($item): float
    {
        $qtd = (float)($item->quantidade ?? 0);
        $total = (float)($item->preco_total ?? 0);

        if ($total <= 0) {
            $total = (float)($item->preco_unitario ?? 0) * $qtd;
        }

        $total = max($total - (float)($item->valor_desconto ?? 0), 0);

        return (float)$total;
    }

    /**
     * Garante que existe linha em appestoque (empresa_id + produto_id).
     * Usa insertOrIgnore para não quebrar no unique e não usar raw.
     */
    private function garantirLinhaEstoque(int $empresaId, int $produtoId, $codfab = null): void
    {
        DB::table('appestoque')->insertOrIgnore([
            'empresa_id'        => $empresaId,
            'produto_id'        => $produtoId,
            'codfabnumero'      => $codfab,
            'estoque_gerencial' => 0.000,
            'reservado'         => 0.000,
            'avaria'            => 0.000,
            'ultimo_preco_compra' => 0.00,
            'ultimo_preco_venda'  => 0.00,
            'data_ultima_mov'   => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    /**
     * Ajusta estoque de forma segura (sem DB::raw com float interpolado):
     * - abre transação
     * - garante linha
     * - lockForUpdate
     * - calcula novos valores em PHP
     * - update com bindings
     */
    private function ajustarEstoque(
        int $empresaId,
        int $produtoId,
        float $deltaGerencial = 0.0,
        float $deltaReservado = 0.0,
        float $deltaAvaria = 0.0,
        $codfab = null,
        ?float $ultimoPrecoCompra = null,
        ?float $ultimoPrecoVenda = null
    ): void {
        DB::transaction(function () use (
            $empresaId,
            $produtoId,
            $deltaGerencial,
            $deltaReservado,
            $deltaAvaria,
            $codfab,
            $ultimoPrecoCompra,
            $ultimoPrecoVenda
        ) {
            $this->garantirLinhaEstoque($empresaId, $produtoId, $codfab);

            $row = DB::table('appestoque')
                ->where('empresa_id', $empresaId)
                ->where('produto_id', $produtoId)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new \RuntimeException("Falha ao travar estoque (empresa {$empresaId}, produto {$produtoId}).");
            }

            $novoGer = max(((float)$row->estoque_gerencial) + (float)$deltaGerencial, 0.0);
            $novoRes = max(((float)$row->reservado) + (float)$deltaReservado, 0.0);
            $novoAva = max(((float)$row->avaria) + (float)$deltaAvaria, 0.0);

            $data = [
                'estoque_gerencial' => $novoGer,
                'reservado'         => $novoRes,
                'avaria'            => $novoAva,
                'data_ultima_mov'   => now(),
                'updated_at'        => now(),
            ];

            if ($codfab !== null) {
                $data['codfabnumero'] = $codfab;
            }
            if ($ultimoPrecoCompra !== null) {
                $data['ultimo_preco_compra'] = round((float)$ultimoPrecoCompra, 2);
            }
            if ($ultimoPrecoVenda !== null) {
                $data['ultimo_preco_venda'] = round((float)$ultimoPrecoVenda, 2);
            }

            DB::table('appestoque')->where('id', (int)$row->id)->update($data);
        }, 3);
    }

    /**
     * 🔹 Registrar movimentação de entrada (compra confirmada)
     */
    public function registrarEntradaCompra(PedidoCompra $pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $produtoId  = (int)$item->produto_id;
            $quantidade = (float)($item->qtd_disponivel ?? $item->quantidade ?? 0);
            if ($produtoId <= 0 || $quantidade <= 0) continue;

            $codfab = $item->produto->codfabnumero ?? $item->codfabnumero ?? null;

            $totalLinhaLiquido = (float)($item->total_liquido ?? 0);
            $custoUnitario = ($totalLinhaLiquido > 0 && $quantidade > 0)
                ? ($totalLinhaLiquido / $quantidade)
                : (float)($item->preco_unitario ?? 0);

            // estoque: soma gerencial e atualiza ultimo_preco_compra
            $this->ajustarEstoque(
                $empresaId,
                $produtoId,
                +$quantidade,
                0.0,
                0.0,
                $codfab,
                $custoUnitario,
                null
            );

            DB::table('appproduto')
                ->where('id', $produtoId)
                ->update([
                    'preco_compra' => round((float)$custoUnitario, 2),
                    'updated_at'   => now(),
                ]);

            DB::table('appmovestoque')->insert([
                'empresa_id'     => $empresaId,
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'ENTRADA',
                'origem'         => 'COMPRA',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => $quantidade,
                'preco_unitario' => round((float)$custoUnitario, 2),
                'observacao'     => 'Entrada por recebimento da compra',
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * 🔹 Reserva estoque para um pedido de venda (PENDENTE)
     */
    public function reservarVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $valorItem = $this->totalLiquidoItem($item);
            $componentes = $this->explodeItemEmComponentes($item, 'preco_revenda', $valorItem);

            foreach ($componentes as $comp) {
                $produtoId     = (int)$comp['produto_id'];
                $qtd           = (float)$comp['quantidade'];
                $codfab        = $comp['codfab'] ?? null;
                $precoUnitario = (float)($comp['preco_unitario'] ?? 0);

                if ($produtoId <= 0 || $qtd <= 0) continue;

                // estoque: incrementa reservado
                $this->ajustarEstoque(
                    $empresaId,
                    $produtoId,
                    0.0,
                    +$qtd,
                    0.0,
                    $codfab
                );

                DB::table('appmovestoque')->insert([
                    'empresa_id'     => $empresaId,
                    'produto_id'     => $produtoId,
                    'codfabnumero'   => $codfab,
                    'tipo_mov'       => 'SAIDA',
                    'origem'         => 'VENDA',
                    'origem_id'      => $pedido->id,
                    'data_mov'       => now(),
                    'quantidade'     => -$qtd,
                    'preco_unitario' => round($precoUnitario, 2),
                    'observacao'     => 'Reserva de estoque (pedido pendente)',
                    'status'         => 'PENDENTE',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    /**
     * 🔹 Confirma a venda (baixa gerencial e libera reservado)
     */
    public function confirmarSaidaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $valorItem = $this->totalLiquidoItem($item);
            $componentes = $this->explodeItemEmComponentes($item, 'preco_revenda', $valorItem);

            foreach ($componentes as $comp) {
                $produtoId     = (int)$comp['produto_id'];
                $qtd           = (float)$comp['quantidade'];
                $codfab        = $comp['codfab'] ?? null;
                $precoUnitario = (float)($comp['preco_unitario'] ?? 0);
                $nomeProd      = $comp['produto']?->nome ?? $codfab ?? ('ID ' . $produtoId);

                if ($produtoId <= 0 || $qtd <= 0) continue;

                DB::transaction(function () use ($empresaId, $produtoId, $qtd, $codfab, $nomeProd) {
                    $this->garantirLinhaEstoque($empresaId, $produtoId, $codfab);

                    $row = DB::table('appestoque')
                        ->where('empresa_id', $empresaId)
                        ->where('produto_id', $produtoId)
                        ->lockForUpdate()
                        ->first();

                    if (!$row) {
                        throw new \RuntimeException("Não há registro de estoque para {$nomeProd} na empresa {$empresaId}.");
                    }

                    $estoqueAtual = (float)($row->estoque_gerencial ?? 0);
                    if ($estoqueAtual < $qtd) {
                        throw new \RuntimeException(
                            "Estoque insuficiente para {$nomeProd} (empresa {$empresaId}) (disp: {$estoqueAtual}, necessário: {$qtd})."
                        );
                    }

                    $novoGer = max($estoqueAtual - $qtd, 0.0);
                    $novoRes = max(((float)$row->reservado) - $qtd, 0.0);

                    DB::table('appestoque')->where('id', (int)$row->id)->update([
                        'estoque_gerencial' => $novoGer,
                        'reservado'         => $novoRes,
                        'data_ultima_mov'   => now(),
                        'updated_at'        => now(),
                    ]);
                }, 3);

                DB::table('appmovestoque')->insert([
                    'empresa_id'     => $empresaId,
                    'produto_id'     => $produtoId,
                    'codfabnumero'   => $codfab,
                    'tipo_mov'       => 'SAIDA',
                    'origem'         => 'VENDA',
                    'origem_id'      => $pedido->id,
                    'data_mov'       => now(),
                    'quantidade'     => -$qtd,
                    'preco_unitario' => round($precoUnitario, 2),
                    'observacao'     => 'Baixa de estoque por venda confirmada',
                    'status'         => 'CONFIRMADO',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        DB::table('appmovestoque')
            ->where('empresa_id', $empresaId)
            ->where('origem', 'VENDA')
            ->where('origem_id', $pedido->id)
            ->where('status', 'PENDENTE')
            ->update([
                'status'     => 'CONFIRMADO',
                'updated_at' => now(),
            ]);
    }

    /**
     * 🔹 Cancelamento do pedido PENDENTE (libera reserva)
     */
    public function cancelarReservaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $valorItem = $this->totalLiquidoItem($item);
            $componentes = $this->explodeItemEmComponentes($item, 'preco_revenda', $valorItem);

            foreach ($componentes as $comp) {
                $produtoId     = (int)$comp['produto_id'];
                $qtd           = (float)$comp['quantidade'];
                $codfab        = $comp['codfab'] ?? null;
                $precoUnitario = (float)($comp['preco_unitario'] ?? 0);

                if ($produtoId <= 0 || $qtd <= 0) continue;

                // estoque: decrementa reservado
                $this->ajustarEstoque(
                    $empresaId,
                    $produtoId,
                    0.0,
                    -$qtd,
                    0.0,
                    $codfab
                );

                // movimento de estorno (espelha o que foi reservado)
                DB::table('appmovestoque')->insert([
                    'empresa_id'     => $empresaId,
                    'produto_id'     => $produtoId,
                    'codfabnumero'   => $codfab,
                    'tipo_mov'       => 'ENTRADA',
                    'origem'         => 'VENDA',
                    'origem_id'      => $pedido->id,
                    'data_mov'       => now(),
                    'quantidade'     => $qtd,
                    'preco_unitario' => round($precoUnitario, 2),
                    'observacao'     => 'Estorno de reserva (pedido cancelado)',
                    'status'         => 'CONFIRMADO',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        DB::table('appmovestoque')
            ->where('empresa_id', $empresaId)
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
     */
    public function registrarSaidaVenda($pedido): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $valorItem = $this->totalLiquidoItem($item);
            $componentes = $this->explodeItemEmComponentes($item, 'preco_revenda', $valorItem);

            foreach ($componentes as $comp) {
                $produtoId     = (int)$comp['produto_id'];
                $quantidade    = (float)$comp['quantidade'];
                $codfab        = $comp['codfab'] ?? null;
                $precoUnitario = (float)($comp['preco_unitario'] ?? 0);

                if ($produtoId <= 0 || $quantidade <= 0) continue;

                // estoque: decrementa gerencial
                DB::transaction(function () use ($empresaId, $produtoId, $quantidade, $codfab) {
                    $this->garantirLinhaEstoque($empresaId, $produtoId, $codfab);

                    $row = DB::table('appestoque')
                        ->where('empresa_id', $empresaId)
                        ->where('produto_id', $produtoId)
                        ->lockForUpdate()
                        ->first();

                    $novoGer = max(((float)$row->estoque_gerencial) - $quantidade, 0.0);

                    DB::table('appestoque')->where('id', (int)$row->id)->update([
                        'estoque_gerencial' => $novoGer,
                        'data_ultima_mov'   => now(),
                        'updated_at'        => now(),
                    ]);
                }, 3);

                DB::table('appmovestoque')->insert([
                    'empresa_id'     => $empresaId,
                    'produto_id'     => $produtoId,
                    'codfabnumero'   => $codfab,
                    'tipo_mov'       => 'SAIDA',
                    'origem'         => 'VENDA',
                    'origem_id'      => $pedido->id,
                    'data_mov'       => now(),
                    'quantidade'     => -$quantidade,
                    'preco_unitario' => round($precoUnitario, 2),
                    'observacao'     => 'Saída por venda confirmada (fluxo direto)',
                    'status'         => 'CONFIRMADO',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    /**
     * 🔹 Ajuste manual de estoque por delta (positivo/negativo)
     */
    public function registrarMovimentoManual(
        int $produtoId,
        string $tipoMov,
        float $quantidade,
        float $precoUnit = 0,
        string $observacao = 'Ajuste manual',
        ?int $empresaId = null
    ): void {
        if ($produtoId <= 0 || $quantidade <= 0) return;

        $empresaId = $this->empresaIdOrFail($empresaId);

        $tipo = strtoupper($tipoMov);
        if (!in_array($tipo, ['ENTRADA', 'SAIDA', 'AJUSTE'], true)) {
            $tipo = 'AJUSTE';
        }

        $produto = Produto::find($produtoId);
        $codfab  = $produto->codfabnumero ?? null;

        $delta = $quantidade;
        if ($tipo === 'SAIDA') $delta = -$quantidade;

        $this->ajustarEstoque(
            $empresaId,
            $produtoId,
            $delta,
            0.0,
            0.0,
            $codfab
        );

        $tipoRegistro = $tipo === 'AJUSTE'
            ? ($delta >= 0 ? 'ENTRADA' : 'SAIDA')
            : $tipo;

        DB::table('appmovestoque')->insert([
            'empresa_id'     => $empresaId,
            'produto_id'     => $produtoId,
            'codfabnumero'   => $codfab,
            'tipo_mov'       => $tipoRegistro,
            'origem'         => 'AJUSTE',
            'origem_id'      => null,
            'data_mov'       => now(),
            'quantidade'     => $delta,
            'preco_unitario' => round((float)$precoUnit, 2),
            'observacao'     => $observacao ?: 'Ajuste manual',
            'status'         => 'CONFIRMADO',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * 🔹 Ajuste manual definindo estoque final (por empresa)
     */
    public function ajusteManual(
        int $produtoId,
        float $novoEstoque,
        string $motivo = 'Ajuste manual',
        ?int $empresaId = null
    ): void {
        $empresaId = $this->empresaIdOrFail($empresaId);

        $produto = Produto::find($produtoId);
        if (!$produto) return;

        $codfab = $produto->codfabnumero ?? null;

        DB::transaction(function () use ($empresaId, $produtoId, $novoEstoque, $motivo, $codfab) {
            $this->garantirLinhaEstoque($empresaId, $produtoId, $codfab);

            $row = DB::table('appestoque')
                ->where('empresa_id', $empresaId)
                ->where('produto_id', $produtoId)
                ->lockForUpdate()
                ->first();

            $atual = (float)($row->estoque_gerencial ?? 0);
            $ajuste = (float)$novoEstoque - $atual;

            if ($ajuste == 0.0) return;

            DB::table('appestoque')->where('id', (int)$row->id)->update([
                'estoque_gerencial' => max((float)$novoEstoque, 0.0),
                'codfabnumero'      => $codfab,
                'data_ultima_mov'   => now(),
                'updated_at'        => now(),
            ]);

            DB::table('appmovestoque')->insert([
                'empresa_id'     => $empresaId,
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
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
        }, 3);
    }

    /**
     * 🔹 Estornar uma entrada de compra (cancelamento) (por empresa)
     */
    public function estornarEntradaCompra(PedidoCompra $pedido, $motivo = 'Cancelamento de pedido'): void
    {
        if (!$pedido || !$pedido->itens) return;

        $empresaId = $this->empresaIdOrFail(null, $pedido);

        foreach ($pedido->itens as $item) {
            $produtoId  = (int)$item->produto_id;
            $quantidade = (float)($item->qtd_disponivel ?? $item->quantidade ?? 0);
            if ($produtoId <= 0 || $quantidade <= 0) continue;

            $codfab = $item->produto->codfabnumero ?? $item->codfabnumero ?? null;

            // estoque: decrementa gerencial
            DB::transaction(function () use ($empresaId, $produtoId, $quantidade, $codfab) {
                $this->garantirLinhaEstoque($empresaId, $produtoId, $codfab);

                $row = DB::table('appestoque')
                    ->where('empresa_id', $empresaId)
                    ->where('produto_id', $produtoId)
                    ->lockForUpdate()
                    ->first();

                $novoGer = max(((float)$row->estoque_gerencial) - $quantidade, 0.0);

                DB::table('appestoque')->where('id', (int)$row->id)->update([
                    'estoque_gerencial' => $novoGer,
                    'data_ultima_mov'   => now(),
                    'updated_at'        => now(),
                ]);
            }, 3);

            DB::table('appmovestoque')->insert([
                'empresa_id'     => $empresaId,
                'produto_id'     => $produtoId,
                'codfabnumero'   => $codfab,
                'tipo_mov'       => 'SAIDA',
                'origem'         => 'DEVOLUCAO',
                'origem_id'      => $pedido->id,
                'data_mov'       => now(),
                'quantidade'     => -$quantidade,
                'preco_unitario' => round((float)($item->preco_unitario ?? 0), 2),
                'observacao'     => $motivo,
                'status'         => 'CONFIRMADO',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * 🔸 Helper: explode um item de pedido em componentes de estoque
     * - Se for KIT e houver $campoBase e $valorKitTotal, aplica RATEIO (opção C)
     *   usando appproduto.$campoBase como "V.Unit" (base).
     *
     * @param  mixed       $item
     * @param  string|null $campoBase     'preco_compra' (compra) ou 'preco_revenda' (venda)
     * @param  float|null  $valorKitTotal total do ITEM (linha) para ratear (ex.: valor líquido)
     */
    private function explodeItemEmComponentes($item, ?string $campoBase = null, ?float $valorKitTotal = null): array
    {
        $resultado = [];

        $produto = $item->produto ?? null;
        $qtdItem  = (float)($item->quantidade ?? 0);

        $isKit = $produto
            && ($produto->tipo ?? null) === 'K'
            && $produto->itensDoKit
            && $produto->itensDoKit->count() > 0;

        // KIT
        if ($isKit) {
            $componentes = [];

            foreach ($produto->itensDoKit as $kitComp) {
                $produtoBase = $kitComp->produtoItem ?? null;
                if (!$produtoBase) continue;

                $qtdComp = $qtdItem * (float)($kitComp->quantidade ?? 0);
                if ($qtdComp <= 0) continue;

                $componentes[] = [
                    'produto_id' => (int)$produtoBase->id,
                    'codfab'     => $produtoBase->codfabnumero ?? null,
                    'quantidade' => (float)$qtdComp,
                    'produto'    => $produtoBase,
                ];
            }

            if (empty($componentes)) {
                return $resultado;
            }

            // Rateio (opção C)
            if ($campoBase && $valorKitTotal !== null && $valorKitTotal > 0) {
                $campoBase = trim($campoBase);

                // base_total = soma( V.Unit_base * qtdComp )
                $baseTotal = 0.0;
                foreach ($componentes as $c) {
                    $p = $c['produto'];
                    $vUnitBase = (float)($p->{$campoBase} ?? 0);
                    $baseTotal += ($vUnitBase * (float)$c['quantidade']);
                }

                // Se não tiver base (tudo 0), cai num rateio simples por quantidade
                if ($baseTotal <= 0) {
                    $qtdTotal = 0.0;
                    foreach ($componentes as $c) {
                        $qtdTotal += (float)$c['quantidade'];
                    }
                    $qtdTotal = $qtdTotal > 0 ? $qtdTotal : 1.0;

                    foreach ($componentes as $c) {
                        $shareTotal = $valorKitTotal * ((float)$c['quantidade'] / $qtdTotal);
                        $precoUnitRateado = round($shareTotal / (float)$c['quantidade'], 2);

                        $resultado[] = [
                            'produto_id'     => $c['produto_id'],
                            'codfab'         => $c['codfab'],
                            'quantidade'     => (float)$c['quantidade'],
                            'preco_unitario' => $precoUnitRateado,
                            'produto'        => $c['produto'],
                        ];
                    }

                    return $resultado;
                }

                // Rateio em centavos (minimiza erro)
                $totalCents  = (int) round($valorKitTotal * 100);
                $sharesCents = [];
                $somaCents   = 0;

                foreach ($componentes as $idx => $c) {
                    $p = $c['produto'];
                    $vUnitBase = (float)($p->{$campoBase} ?? 0);
                    $base = $vUnitBase * (float)$c['quantidade'];

                    $share = (int) floor($totalCents * ($base / $baseTotal));
                    $sharesCents[$idx] = $share;
                    $somaCents += $share;
                }

                // Ajusta o resto no último componente
                $resto = $totalCents - $somaCents;
                if ($resto !== 0) {
                    $lastIdx = array_key_last($sharesCents);
                    $sharesCents[$lastIdx] = ($sharesCents[$lastIdx] ?? 0) + $resto;
                }

                foreach ($componentes as $idx => $c) {
                    $shareTotal = ($sharesCents[$idx] ?? 0) / 100.0;
                    $qtdComp = (float)$c['quantidade'];
                    $precoUnitRateado = $qtdComp > 0 ? round($shareTotal / $qtdComp, 2) : 0.0;

                    $resultado[] = [
                        'produto_id'     => $c['produto_id'],
                        'codfab'         => $c['codfab'],
                        'quantidade'     => $qtdComp,
                        'preco_unitario' => $precoUnitRateado,
                        'produto'        => $c['produto'],
                    ];
                }

                return $resultado;
            }

            // Sem rateio: mantém 0
            foreach ($componentes as $c) {
                $resultado[] = [
                    'produto_id'     => $c['produto_id'],
                    'codfab'         => $c['codfab'],
                    'quantidade'     => (float)$c['quantidade'],
                    'preco_unitario' => 0.0,
                    'produto'        => $c['produto'],
                ];
            }

            return $resultado;
        }

        // Produto normal
        $produtoId = (int)($item->produto_id ?? 0);
        if ($produtoId > 0 && $qtdItem > 0) {
            if ($valorKitTotal !== null && $valorKitTotal > 0) {
                $precoUnit = round(((float)$valorKitTotal) / $qtdItem, 2);
            } else {
                $precoUnit = (float)($item->preco_unitario ?? 0);
                if ($precoUnit <= 0 && $campoBase && $produto && isset($produto->{$campoBase})) {
                    $precoUnit = (float)($produto->{$campoBase} ?? 0);
                }
            }

            $resultado[] = [
                'produto_id'     => $produtoId,
                'codfab'         => $item->codfabnumero ?? ($produto->codfabnumero ?? null),
                'quantidade'     => $qtdItem,
                'preco_unitario' => (float)$precoUnit,
                'produto'        => $produto,
            ];
        }

        return $resultado;
    }
}