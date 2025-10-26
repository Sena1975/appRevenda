<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class IBGELocalizacaoSeeder extends Seeder
{
    public function run()
    {
        DB::table('appbairro')->truncate();
        DB::table('appcidade')->truncate();
        DB::table('appuf')->truncate();

        // 🏙️ Importa estados e cidades do IBGE
        $ufs = Http::get('https://servicodados.ibge.gov.br/api/v1/localidades/estados')->json();

        foreach ($ufs as $uf) {
            $ufId = DB::table('appuf')->insertGetId([
                'nome' => $uf['nome'],
                'sigla' => $uf['sigla'],
                'codigo_ibge' => $uf['id'],
            ]);

            $cidades = Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf['id']}/municipios")->json();

            foreach ($cidades as $cidade) {
                DB::table('appcidade')->insert([
                    'nome' => $cidade['nome'],
                    'codigo_ibge' => $cidade['id'],
                    'uf_id' => $ufId,
                ]);
            }

            $this->command->info("✅ {$uf['sigla']} ({$uf['nome']}) importado!");
            sleep(1);
        }

        // 🏘️ Popula alguns bairros reais via OpenStreetMap
        $amostraCidades = DB::table('appcidade')->inRandomOrder()->limit(10)->get();

        foreach ($amostraCidades as $cidade) {
            $url = "https://nominatim.openstreetmap.org/search?city={$cidade->nome}&country=Brazil&format=json&addressdetails=1&extratags=1";
            $response = Http::withHeaders(['User-Agent' => 'LaravelSeeder'])->get($url);

            if ($response->successful()) {
                $nomes = collect($response->json())->pluck('display_name')->take(8);
                foreach ($nomes as $bairro) {
                    DB::table('appbairro')->insert([
                        'nome' => strtok($bairro, ','),
                        'cidade_id' => $cidade->id,
                    ]);
                }
                $this->command->info("🏘️ Bairros importados para {$cidade->nome}");
            }

            sleep(2);
        }

        $this->command->info("🎯 Importação concluída com sucesso!");
    }
}
