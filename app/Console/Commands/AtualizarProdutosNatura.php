<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ProdutoNatura;

class AtualizarProdutosNatura extends Command
{
    protected $signature = 'natura:atualizar-produtos 
        {--categoria=perfumaria-feminina : Slugs separados por vírgula (ex.: perfumaria-feminina,perfumaria-masculina,maquiagem)}
        {--ciclo= : Texto do ciclo, ex.: "17 | 24/10 a 13/11"}
        {--count=48 : Quantidade por página (até 100)}';

    protected $description = 'Atualiza produtos da Natura via BFF (paginado) usando Authorization Bearer + tenant_id, com credenciais por categoria';

    public function handle()
    {
        $this->info('🔄 Coletando produtos da Natura...');

        $baseUrl     = env('NATURA_BFF_URL', 'https://www.natura.com.br/bff-app-natura-brazil/search');
        $tenantName  = trim(env('NATURA_TENANT_NAME', 'tenant_id'));
        $tenantValue = trim(env('NATURA_TENANT_VALUE', 'brazil-natura-web'));

        $cicloTexto = (string) $this->option('ciclo');
        $perPage    = (int) $this->option('count');
        if ($perPage <= 0 || $perPage > 100) $perPage = 48;

        $categorias = collect(explode(',', (string)$this->option('categoria')))
            ->map(fn($s) => trim($s))
            ->filter()
            ->values();

        if ($categorias->isEmpty()) {
            $this->error('❌ Informe ao menos uma categoria em --categoria=');
            return self::FAILURE;
        }

        $this->line("➡️  Tenant header: {$tenantName} = {$tenantValue}");
        $cicloTexto !== '' 
            ? $this->line("➡️  Ciclo atual: {$cicloTexto}")
            : $this->warn("⚠️  Sem --ciclo informado. 'ciclo_data' ficará vazio.");

        $totalGeral = 0;

        foreach ($categorias as $categoria) {
            $key = strtoupper(str_replace('-', '_', $categoria)); // perfumaria-feminina -> PERFUMARIA_FEMININA

            // Lê Bearer/Cookie específicos da categoria, com fallback nas globais
            $bearer = trim(env("NATURA_BEARER_{$key}", env('NATURA_BEARER', '')));
            $cookie = trim(env("NATURA_COOKIE_{$key}", env('NATURA_COOKIE', '')));

            if ($bearer === '') {
                $this->warn("⚠️  Sem NATURA_BEARER_{$key} e sem NATURA_BEARER global. Pulando categoria '{$categoria}'.");
                continue;
            }

            $headers = [
                'Accept'           => '*/*',
                'Accept-Language'  => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127 Safari/537.36',
                'Origin'           => 'https://www.natura.com.br',
                'Referer'          => 'https://www.natura.com.br/',
                'Authorization'    => $bearer,
                $tenantName        => $tenantValue,
            ];
            if ($cookie !== '') {
                $headers['Cookie'] = $cookie; // precisa ser o header Cookie, não a URL
            }

            $this->newLine();
            $this->info("📦 Categoria: {$categoria}");
            $this->line("   🔑 using: NATURA_BEARER_{$key}" . ($cookie !== '' ? " + NATURA_COOKIE_{$key}" : ''));

            $params = [
                'count'     => $perPage,
                'q'         => '',
                'expand'    => 'prices,availability,images,variations',
                'sort'      => 'top-sellers',
                'refine_1'  => 'cgid=' . $categoria,
                'apiMode'   => 'product',
                'start'     => 0,
            ];

            $totalCat = 0;

            do {
                $this->line("➡️  Buscando start={$params['start']}");

                $response = Http::retry(2, 700)
                    ->withOptions([
                        'verify'  => false, // se tiver cacert, mude para true
                        'timeout' => 25,
                    ])
                    ->withHeaders($headers)
                    ->get($baseUrl, $params);

                if ($response->failed()) {
                    $this->warn("❌ HTTP {$response->status()} em start={$params['start']} (cat: {$categoria})");
                    $this->warn('Body (trecho): ' . mb_substr($response->body(), 0, 600));
                    break;
                }

                $json  = $response->json();
                $lista = data_get($json, 'products', []);

                if (!is_array($lista) || count($lista) === 0) {
                    $this->info('✅ Fim da paginação nesta categoria.');
                    break;
                }

                foreach ($lista as $p) {
                    $codigo = data_get($p, 'productId');
                    $nome   = data_get($p, 'name');
                    if (!$codigo || !$nome) continue;

                    ProdutoNatura::updateOrCreate(
                        ['productId' => (string)$codigo],
                        [
                            'name'                => data_get($p, 'name'),
                            'friendlyName'        => data_get($p, 'friendlyName'),
                            'categoryId'          => data_get($p, 'categoryId'),
                            'categoryName'        => data_get($p, 'categoryName'),
                            'classificationId'    => data_get($p, 'classificationId'),
                            'classificationName'  => data_get($p, 'classificationName'),
                            'description'         => data_get($p, 'longDescription'),
                            'price_sales_value'   => data_get($p, 'price.sales.value'),
                            'price_list_value'    => data_get($p, 'price.list.value'),
                            'discount_percent'    => data_get($p, 'price.discountPercent'),
                            'rating'              => data_get($p, 'rating'),
                            'tags'                => json_encode(data_get($p, 'tags')),
                            'images'              => json_encode(data_get($p, 'images')),
                            'url'                 => data_get($p, 'url'),
                            'raw_json'            => json_encode($p),

                            'categoria_slug'      => $categoria,
                            'ciclo_data'          => $cicloTexto,
                        ]
                    );

                    $totalCat++;
                    $totalGeral++;
                }

                $this->info("✅ Página start={$params['start']} concluída. Cat acumulado: {$totalCat}");
                $params['start'] += $perPage;
                usleep(300 * 1000);
            } while (true);

            $this->info("📊 Total coletado na categoria {$categoria}: {$totalCat}");
        }

        $this->newLine();
        $this->info("🎉 Concluído. Total geral: {$totalGeral}");
        return self::SUCCESS;
    }
}
