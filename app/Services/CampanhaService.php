<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\PedidoVenda;
use App\Models\ItemVenda;
use App\Models\Campanha;
use App\Models\CampanhaProduto;

class CampanhaService
{
    /**
     * Avalia e calcula campanhas aplicáveis a um pedido (sem persistir nada no pedido).
     * Retorna um array com:
     *  - applied: [ [campanha_id, nome, tipo_beneficio, valor, itens_afetados=>[id=>valor_desc,...]] ]
     *  - desconto_total: float
     *  - mensagens: string[]
     */
    public function avaliar(PedidoVenda $pedido): array
    {
        $hoje = Carbon::today()->toDateString();

        // 1) campanhas ativas e vigentes
        $campanhas = Campanha::query()
            ->where('status', 1)
            ->whereDate('data_inicio', '<=', $hoje)
            ->whereDate('data_fim', '>=', $hoje)
            ->orderBy('prioridade', 'desc')      // se você tiver campo prioridade
            ->get();

        if ($campanhas->isEmpty()) {
            return ['applied' => [], 'desconto_total' => 0.0, 'mensagens' => ['Nenhuma campanha vigente.']];
        }

        // 2) carrega itens do pedido com informações necessárias
        $itens = $pedido->itens()->with(['produto.categoria', 'produto.subcategoria'])->get();

        $aplicadas = [];
        $descontoTotal = 0.0;
        $mensagens = [];

        foreach ($campanhas as $camp) {
            // Exemplo de estrutura de campanha:
            // tipo_beneficio: 'PERCENTUAL_ITEM','VALOR_ITEM','PERCENTUAL_TOTAL','VALOR_TOTAL','BRINDE'
            // restricoes: verifica por produto_id/categoria_id/subcategoria_id, valor_minimo, qtd_minima, etc.

            $ok = $this->passaRestricoes($camp, $pedido, $itens);
            if (!$ok) continue;

            $resultado = $this->calcularBeneficio($camp, $pedido, $itens);
            if ($resultado['valor'] <= 0) continue;

            $aplicadas[] = [
                'campanha_id'   => $camp->id,
                'nome'          => $camp->nome ?? ('Campanha #' . $camp->id),
                'tipo_beneficio' => $camp->tipo_beneficio,
                'valor'         => (float)$resultado['valor'],
                'itens_afetados' => $resultado['itens_afetados'] ?? [],
            ];

            $descontoTotal += (float)$resultado['valor'];
            if (!empty($resultado['mensagem'])) $mensagens[] = $resultado['mensagem'];

            // Se a regra for “excludente” (só 1 campanha), pode dar break aqui.
            // if ($camp->excludente) break;
        }

        return [
            'applied'        => $aplicadas,
            'desconto_total' => round($descontoTotal, 2),
            'mensagens'      => $mensagens,
        ];
    }

    /**
     * Aplica (persiste) o resultado no pedido:
     * - grava resumo JSON em campo do pedido (ex.: pedido.campanhas_json)
     * - opcional: cria registros em tabela Participacoes
     * - NÃO altera estoque/financeiro (isso fica para confirmar o pedido)
     */
    public function aplicar(PedidoVenda $pedido): array
    {
        $resultado = $this->avaliar($pedido);

        // Exemplo: salvar snapshot em JSON no pedido (adicione esse campo na tabela se ainda não existir)
        $pedido->campanhas_json = json_encode($resultado, JSON_UNESCAPED_UNICODE);
        $pedido->desconto_campanha = $resultado['desconto_total']; // adicione esse campo na tabela apppedidovenda
        $pedido->save();

        // Se tiver tabela de participações, grave aqui:
        // foreach ($resultado['applied'] as $ap) {
        //     Participacao::updateOrCreate(
        //         ['pedido_id'=>$pedido->id, 'campanha_id'=>$ap['campanha_id']],
        //         ['valor_aplicado'=>$ap['valor'], 'snapshot'=>json_encode($ap)]
        //     );
        // }

        return $resultado;
    }

    /** ------------------ PRIVADO: RESTRIÇÕES + CÁLCULO ------------------ */

    private function passaRestricoes($camp, PedidoVenda $pedido, Collection $itens): bool
    {
        // Exemplo de restrições típicas:
        // - valor_minimo_pedido
        // - qtde_minima_itens
        // - somente produtos/categorias/subcategorias da CampanhaProduto (tabela de restrições)

        // valor mínimo do pedido
        if (!empty($camp->valor_minimo) && (float)$pedido->valor_total < (float)$camp->valor_minimo) {
            return false;
        }

        // se houver tabela de restrição por produto/categoria/sub:
        // se não houver nenhuma restrição cadastrada, considere "liberado para todos".
        if (method_exists($camp, 'restricoes')) {
            $restr = $camp->restricoes; // collection CampanhaProduto
            if ($restr->isNotEmpty()) {
                // ao menos 1 item do pedido precisa “bater” com uma das restrições
                $bateu = $itens->contains(function ($it) use ($restr) {
                    return $restr->contains(function ($r) use ($it) {
                        return ($r->produto_id      && $it->produto_id == $r->produto_id) ||
                            ($r->categoria_id    && $it->produto?->categoria_id == $r->categoria_id) ||
                            ($r->subcategoria_id && $it->produto?->subcategoria_id == $r->subcategoria_id);
                    });
                });
                if (!$bateu) return false;
            }
        }

        return true;
    }

    private function calcularBeneficio($camp, PedidoVenda $pedido, Collection $itens): array
    {
        $tipo = $camp->tipo_beneficio; // ex.: PERCENTUAL_ITEM, VALOR_TOTAL, etc.
        $valor = 0.0;
        $itensAfetados = [];
        $msg = null;

        switch ($tipo) {
            case 'PERCENTUAL_ITEM':
                // aplica % em itens elegíveis
                $perc = (float)$camp->percentual; // 0–100
                foreach ($itens as $it) {
                    if (!$this->itemElegivel($camp, $it)) continue;
                    $desc = round(($it->preco_unitario * $it->quantidade) * ($perc / 100), 2);
                    if ($desc > 0) {
                        $valor += $desc;
                        $itensAfetados[$it->id] = $desc;
                    }
                }
                $msg = "Desconto de {$perc}% aplicado em itens elegíveis.";
                break;

            case 'VALOR_ITEM':
                $valorUnit = (float)$camp->valor_fixo_item;
                foreach ($itens as $it) {
                    if (!$this->itemElegivel($camp, $it)) continue;
                    $desc = round($valorUnit * $it->quantidade, 2);
                    if ($desc > 0) {
                        $valor += $desc;
                        $itensAfetados[$it->id] = $desc;
                    }
                }
                $msg = "Desconto fixo por item aplicado.";
                break;

            case 'PERCENTUAL_TOTAL':
                $perc = (float)$camp->percentual;
                $valor = round($pedido->valor_total * ($perc / 100), 2);
                $msg = "Desconto de {$perc}% no total do pedido.";
                break;

            case 'VALOR_TOTAL':
                $valor = round((float)$camp->valor_fixo_total, 2);
                $msg = "Desconto fixo no total do pedido.";
                break;

            case 'BRINDE':
                // aqui você só marca mensagem; a lógica de gerar item-brinde pode ser feita ao confirmar
                $valor = 0.0;
                $msg = "Brinde aplicado (ver detalhes da campanha).";
                break;
        }

        return [
            'valor'         => max(0, $valor),
            'itens_afetados' => $itensAfetados,
            'mensagem'      => $msg,
        ];
    }

    private function itemElegivel($camp, ItemVenda $it): bool
    {
        if (!method_exists($camp, 'restricoes')) return true;
        $restr = $camp->restricoes;
        if ($restr->isEmpty()) return true;

        return $restr->contains(function ($r) use ($it) {
            return ($r->produto_id      && $it->produto_id == $r->produto_id) ||
                ($r->categoria_id    && $it->produto?->categoria_id == $r->categoria_id) ||
                ($r->subcategoria_id && $it->produto?->subcategoria_id == $r->subcategoria_id);
        });
    }
    /**
     * Retorna as campanhas vigentes filtrando pelo campo metodo_php.
     *
     * @param  string  $metodoPhp  Valor exato que está no campo campanha.metodo_php
     * @return \Illuminate\Support\Collection|\App\Models\Campanha[]
     */
    public function campanhasVigentesPorMetodo(string $metodoPhp): Collection
    {
        $hoje = Carbon::today()->toDateString();

        return Campanha::query()
            ->where('ativa', true)
            ->whereDate('data_inicio', '<=', $hoje)
            ->whereDate('data_fim', '>=', $hoje)
            ->where('metodo_php', $metodoPhp)
            ->orderBy('prioridade', 'desc')
            ->get();
    }

    /**
     * Retorna apenas os IDs das campanhas vigentes filtradas por metodo_php.
     *
     * @param  string  $metodoPhp
     * @return int[]
     */
    public function campanhasVigentesIdsPorMetodo(string $metodoPhp): array
    {
        return $this->campanhasVigentesPorMetodo($metodoPhp)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

}
