<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaCliente;
use App\Models\CampanhaCupom;
use Illuminate\Support\Facades\DB;

class CampaignEvaluatorService
{
    /**
     * Avalia e aplica campanhas ao pedido (gerando cupons e ajustando saldos).
     * NÃO remove cupons anteriores. Para idempotência, use reavaliarPedido().
     * @param  \App\Models\PedidoVenda $pedido
     * @return array  ['aplicadas' => [], 'gerou_cupom' => int, 'mensagens' => []]
     */
    public function avaliarPedido($pedido): array
    {
        $result = ['aplicadas' => [], 'gerou_cupom' => 0, 'mensagens' => []];

        // 1) Buscar campanhas em vigência e ativas
        $hoje = now()->toDateString();
        $campanhas = Campanha::with(['produtos', 'premios', 'tipo'])
            ->where('ativa', 1)
            ->whereDate('data_inicio', '<=', $hoje)
            ->whereDate('data_fim', '>=', $hoje)
            ->orderBy('prioridade', 'asc') // menor número = maior prioridade
            ->get();

        if ($campanhas->isEmpty()) {
            $result['mensagens'][] = 'Nenhuma campanha ativa/vigente.';
            return $result;
        }

        // 2) Determinar itens elegíveis por campanha
        $itensPedido = collect($pedido->itens ?? []);

        $candidatas = [];
        foreach ($campanhas as $campanha) {
            $itensElegiveis = $this->filtrarItensElegiveis($campanha, $itensPedido);

            if ($itensElegiveis->isEmpty()) {
                if ($campanha->produtos->count() > 0) continue; // tem restrição e nada bateu
                $itensElegiveis = $itensPedido; // sem restrição => todos
            }

            $avaliacao = $this->avaliarRegra($campanha, $pedido, $itensElegiveis);

            if ($avaliacao['aplicavel'] === true) {
                $candidatas[] = [
                    'campanha'  => $campanha,
                    'itens'     => $itensElegiveis,
                    'avaliacao' => $avaliacao,
                ];
            }
        }

        if (empty($candidatas)) {
            $result['mensagens'][] = 'Nenhuma campanha aplicável ao pedido.';
            return $result;
        }

        // 3) Resolver conflito de campanhas não cumulativas: fica só a de maior prioridade
        $naoCumulativas = array_filter($candidatas, fn($c) => !$c['campanha']->isCumulativa());
        if (!empty($naoCumulativas)) {
            $candidatas = [reset($naoCumulativas)];
        }

        // 4) Aplicar campanhas (gerar cupons / atualizar saldo)
        DB::beginTransaction();
        try {
            foreach ($candidatas as $cand) {
                $aplicou = $this->aplicarCampanha($cand['campanha'], $pedido, $cand['itens'], $cand['avaliacao']);
                if ($aplicou['gerou_cupons'] > 0) {
                    $result['gerou_cupom'] += $aplicou['gerou_cupons'];
                }
                $result['aplicadas'][] = [
                    'campanha_id' => $cand['campanha']->id,
                    'nome'        => $cand['campanha']->nome,
                    'detalhe'     => $aplicou['mensagem'] ?? 'Aplicada.'
                ];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $result['mensagens'][] = 'Erro ao aplicar campanhas: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Idempotente: reverte cupons/saldos gerados por ESTE pedido e reavalia do zero.
     */
    public function reavaliarPedido($pedido): array
    {
        DB::beginTransaction();
        try {
            $this->reverterCampanhasDoPedido($pedido);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['aplicadas' => [], 'gerou_cupom' => 0, 'mensagens' => ['Erro ao reverter: '.$e->getMessage()]];
        }

        // Agora avalia novamente com base no estado atual do pedido
        return $this->avaliarPedido($pedido->fresh('itens'));
    }

    /**
     * Reverte os efeitos de campanhas geradas para ESTE pedido:
     * - Deleta cupons em appcampanha_cupom vinculados a pedido_id
     * - Ajusta saldos em appcampanha_cliente (saldo_valor, saldo_quantidade, total_cupons)
     */
    public function reverterCampanhasDoPedido($pedido): void
    {
        // Busca cupons por pedido
        $cupons = CampanhaCupom::where('pedido_id', $pedido->id)->get();

        if ($cupons->isEmpty()) {
            return;
        }

        // Agrupa por campanha_id e tipo_geracao para ajustar saldos
        $porCampanha = $cupons->groupBy('campanha_id');

        foreach ($porCampanha as $campanhaId => $lista) {
            $porTipo = $lista->groupBy('tipo_geracao');

            // total cupons gerados por esta campanha neste pedido
            $totalCupons = $lista->count();

            // Ajusta CampanhaCliente
            $campCli = CampanhaCliente::where('campanha_id', $campanhaId)
                ->where('cliente_id', $pedido->cliente_id)
                ->first();

            if ($campCli) {
                // Subtrai total_cupons
                $campCli->total_cupons = max(0, (int)($campCli->total_cupons ?? 0) - $totalCupons);

                // Se houve cupons por valor, subtrair a soma dos valores de referência
                if ($porTipo->has('valor')) {
                    $somaValor = (float) $porTipo['valor']->sum(fn($c) => (float)($c->valor_referencia ?? 0));
                    $campCli->saldo_valor = max(0, (float)($campCli->saldo_valor ?? 0) - $somaValor);
                }

                // Se houve cupons por quantidade, subtrair a soma das quantidades de referência
                if ($porTipo->has('quantidade')) {
                    $somaQtd = (int) $porTipo['quantidade']->sum(fn($c) => (int)($c->quantidade_referencia ?? 0));
                    $campCli->saldo_quantidade = max(0, (int)($campCli->saldo_quantidade ?? 0) - $somaQtd);
                }

                $campCli->atualizado_em = now();
                $campCli->save();
            }
        }

        // Por fim, exclui os cupons do pedido
        CampanhaCupom::where('pedido_id', $pedido->id)->delete();
    }

    /**
     * Filtra itens elegíveis pela campanha (produto/codfab/categoria).
     */
    protected function filtrarItensElegiveis(Campanha $campanha, $itensPedido)
    {
        $restricoes = $campanha->produtos; // linhas em appcampanha_produto
        if ($restricoes->count() === 0) {
            return collect($itensPedido);
        }

        return collect($itensPedido)->filter(function ($item) use ($restricoes) {
            foreach ($restricoes as $r) {
                $okProduto   = $r->produto_id && (int)$r->produto_id === (int)($item->produto_id ?? 0);
                $okCodfab    = $r->codfabnumero && $r->codfabnumero === ($item->codfabnumero ?? null);
                $okCategoria = $r->categoria_id && (int)$r->categoria_id === (int)($item->categoria_id ?? 0);

                if ($okProduto || $okCodfab || $okCategoria) {
                    return true;
                }
            }
            return false;
        })->values();
    }

    /**
     * Avalia se a campanha é aplicável e calcula quantos cupons geraria.
     * Retorna: ['aplicavel'=>bool, 'qtd_cupons'=>int, 'tipo'=>'valor|quantidade|brinde', ...]
     */
    protected function avaliarRegra(Campanha $campanha, $pedido, $itensElegiveis): array
    {
        $tipoId = (int)$campanha->tipo_id;

        $valorElegivel = (float) collect($itensElegiveis)->sum(fn($i) => (float)($i->valor_total ?? 0));
        $qtdElegivel   = (int) collect($itensElegiveis)->sum(fn($i) => (int)($i->quantidade ?? 0));

        // 1 - Cupom por valor
        if ($tipoId === 1) {
            $base = (float) ($campanha->valor_base_cupom ?? 0);
            if ($base <= 0) return ['aplicavel'=>false, 'qtd_cupons'=>0];

            $qtd = (int) floor($valorElegivel / $base);
            return [
                'aplicavel' => $qtd > 0,
                'qtd_cupons' => $qtd,
                'tipo' => 'valor',
                'base' => $base,
                'valor_referencia' => $valorElegivel,
            ];
        }

        // 2 - Cupom por quantidade
        if ($tipoId === 2) {
            $min = (int) ($campanha->quantidade_minima_cupom ?? 0);
            if ($min <= 0) return ['aplicavel'=>false, 'qtd_cupons'=>0];

            $qtd = (int) floor($qtdElegivel / $min);
            return [
                'aplicavel' => $qtd > 0,
                'qtd_cupons' => $qtd,
                'tipo' => 'quantidade',
                'base' => $min,
                'quantidade_referencia' => $qtdElegivel,
            ];
        }

        // 3 - Brinde (placeholder)
        if ($tipoId === 3) {
            $aplicavel = collect($itensElegiveis)->count() > 0;
            return [
                'aplicavel' => $aplicavel,
                'qtd_cupons' => 0,
                'tipo' => 'brinde',
            ];
        }

        return ['aplicavel'=>false, 'qtd_cupons'=>0];
    }

    /**
     * Aplica a campanha: gera cupons e atualiza saldos em appcampanha_cliente.
     */
    protected function aplicarCampanha(Campanha $campanha, $pedido, $itensElegiveis, array $avaliacao): array
    {
        $clienteId = $pedido->cliente_id;
        $agora = now();
        $gerou = 0;
        $mensagem = null;

        // Garante linha em appcampanha_cliente
        $campCli = CampanhaCliente::firstOrCreate(
            ['campanha_id' => $campanha->id, 'cliente_id' => $clienteId],
            ['saldo_valor' => 0, 'saldo_quantidade' => 0, 'total_cupons' => 0, 'atualizado_em' => $agora]
        );

        if (in_array($avaliacao['tipo'] ?? '', ['valor', 'quantidade'])) {
            $qtd = (int) $avaliacao['qtd_cupons'];
            if ($qtd > 0) {
                for ($i=0; $i<$qtd; $i++) {
                    CampanhaCupom::create([
                        'campanha_id' => $campanha->id,
                        'cliente_id' => $clienteId,
                        'codfabnumero' => null, // se precisar, preencha por item
                        'pedido_id' => $pedido->id,
                        'codigo_cupom' => CampanhaCupom::gerarCodigo(),
                        'tipo_geracao' => $avaliacao['tipo'],
                        'valor_referencia' => $avaliacao['valor_referencia'] ?? null,
                        'quantidade_referencia' => $avaliacao['quantidade_referencia'] ?? null,
                        'data_geracao' => $agora,
                        'utilizado' => 0,
                    ]);
                    $gerou++;
                }

                if ($avaliacao['tipo'] === 'valor') {
                    $campCli->saldo_valor = ($campCli->saldo_valor ?? 0) + (float) ($avaliacao['valor_referencia'] ?? 0);
                } else {
                    $campCli->saldo_quantidade = ($campCli->saldo_quantidade ?? 0) + (int) ($avaliacao['quantidade_referencia'] ?? 0);
                }

                $campCli->total_cupons = ($campCli->total_cupons ?? 0) + $qtd;
                $campCli->atualizado_em = $agora;
                $campCli->save();

                $mensagem = "Gerados {$qtd} cupom(ns).";
            }
        }

        if (($avaliacao['tipo'] ?? '') === 'brinde') {
            $mensagem = 'Campanha de brinde elegível (implementar ação do brinde).';
        }

        return ['gerou_cupons' => $gerou, 'mensagem' => $mensagem];
    }
}
