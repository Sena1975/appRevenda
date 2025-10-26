<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BairrosSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏘️  Iniciando criação automática de bairros para cada cidade...\n";

        // Desabilita chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('appbairro')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Busca todas as cidades cadastradas
        $cidades = DB::table('appcidade')->select('id', 'nome')->get();
        $bairrosGerados = 0;

        // Bairros genéricos para cada cidade
        $bairrosPadrao = ['Centro', 'Zona Norte', 'Zona Sul', 'Zona Leste', 'Zona Oeste'];

        foreach ($cidades as $cidade) {
            foreach ($bairrosPadrao as $bairroNome) {
                DB::table('appbairro')->insert([
                    'nome' => $bairroNome,
                    'cidade_id' => $cidade->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $bairrosGerados++;
            }
        }

        echo "✅ Foram criados {$bairrosGerados} bairros genéricos em {$cidades->count()} cidades.\n";
        echo "🎉 Geração de bairros concluída com sucesso!\n";
    }
}
