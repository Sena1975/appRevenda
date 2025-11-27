<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampanhaIndicacaoSeeder extends Seeder
{
    /**
     * Cria/atualiza a campanha padrão de indicação
     * e vincula as indicações sem campanha a ela.
     */
    public function run(): void
    {
        // 1) Pega QUALQUER tipo de campanha existente
        // (primeiro registro da tabela)
        $tipoId = DB::table('appcampanha_tipo')->value('id');

        if (! $tipoId) {
            // Se não tiver nenhum tipo cadastrado, aborta com aviso
            $this->command?->warn('Nenhum registro encontrado em appcampanha_tipo. Crie pelo menos um tipo antes de rodar este seeder.');
            return;
        }

        // 2) Dados base da campanha de indicação
        $dadosCampanha = [
            'descricao'                   => 'Campanha de indicação: indicado ganha 5% de desconto na 1ª compra e indicador recebe prêmio em PIX.',
            'tipo_id'                     => $tipoId,
            'data_inicio'                 => '2025-01-01',
            'data_fim'                    => '2099-12-31',
            'ativa'                       => 1,
            'cumulativa'                  => 0,
            'aplicacao_automatica'        => 1,
            'prioridade'                  => 5,
            'valor_base_cupom'            => null,
            'acumulativa_por_valor'       => 0,
            'acumulativa_por_quantidade'  => 0,
            'quantidade_minima_cupom'     => null,
            'tipo_acumulacao'             => null,
            'produto_brinde_id'           => null,
        ];

        // 3) Procura campanha pelo NOME "Campanha de Indicação"
        $campanhaId = DB::table('appcampanha')
            ->where('nome', 'Campanha de Indicação')
            ->value('id');

        if ($campanhaId) {
            // Atualiza se já existe
            DB::table('appcampanha')
                ->where('id', $campanhaId)
                ->update($dadosCampanha);
        } else {
            // Cria se não existe
            $campanhaId = DB::table('appcampanha')->insertGetId(
                array_merge(['nome' => 'Campanha de Indicação'], $dadosCampanha)
            );
        }

        // 4) Vincula todas as indicações que ainda não têm campanha
        DB::table('appindicacao')
            ->whereNull('campanha_id')
            ->update(['campanha_id' => $campanhaId]);
    }
}
