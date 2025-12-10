<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campanha;
use App\Models\MensagemModelo;
use App\Models\CampanhaMensagem;

class CampanhaMensagemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Busca uma campanha de indicação (ajuste se tiver mais de uma)
        $campanhaIndicacao = Campanha::where('metodo_php', 'isCampanhaIndicacao')
            ->where('ativa', 1)
            ->orderBy('prioridade', 'asc')
            ->first();

        if (! $campanhaIndicacao) {
            // Se não tiver campanha de indicação cadastrada, não faz nada
            return;
        }

        // Mapa de evento => código do modelo
        $mapaEventos = [
            'indicacao_pedido_pendente'             => [
                'codigo_modelo' => 'indicacao_pedido_pendente',
                'delay_minutos' => null,
            ],
            'indicacao_premio_pix'                  => [
                'codigo_modelo' => 'indicacao_premio_pix',
                'delay_minutos' => null,
            ],
            'convite_indicacao_primeira_compra'     => [
                'codigo_modelo' => 'convite_indicacao_primeira_compra',
                'delay_minutos' => 1440, // 24h
            ],
        ];

        foreach ($mapaEventos as $evento => $cfg) {
            $modelo = MensagemModelo::where('codigo', $cfg['codigo_modelo'])->first();

            if (! $modelo) {
                // Se não existir o modelo, pula (talvez ainda não foi seedado)
                continue;
            }

            CampanhaMensagem::updateOrCreate(
                [
                    'campanha_id' => $campanhaIndicacao->id,
                    'evento'      => $evento,
                ],
                [
                    'mensagem_modelo_id' => $modelo->id,
                    'delay_minutos'      => $cfg['delay_minutos'],
                    'ativo'              => true,
                ]
            );
        }
    }
}
