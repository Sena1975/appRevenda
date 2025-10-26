<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CidadesSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 Importando UFs e cidades do IBGE...\n";

        // ⚙️ Desativa temporariamente as restrições de chave estrangeira
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 🧹 Limpa as tabelas antes da importação
        DB::table('appbairro')->delete();
        DB::table('appcidade')->delete();
        DB::table('appuf')->delete();

        // ✅ Reativa as restrições
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🌎 Obtém lista de estados (UFs) do IBGE
        $ufs = Http::get('https://servicodados.ibge.gov.br/api/v1/localidades/estados')
            ->json();

        foreach ($ufs as $uf) {
            // 🗂️ Insere o estado na tabela appuf
            $ufId = DB::table('appuf')->insertGetId([
                'sigla' => $uf['sigla'],
                'nome' => $uf['nome'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 🏙️ Obtém cidades (municípios) do estado
            $cidades = Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf['id']}/municipios")
                ->json();

            $contador = 0;
            foreach ($cidades as $cidade) {
                DB::table('appcidade')->insert([
                    'nome' => $cidade['nome'],
                    'codigoibge' => $cidade['id'],
                    'uf_id' => $ufId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $contador++;
            }

            echo "✅ {$uf['nome']} importado com sucesso ({$contador} cidades)\n";
        }

        echo "🎉 Importação concluída com sucesso!\n";
    }
}
