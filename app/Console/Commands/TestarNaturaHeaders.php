<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestarNaturaHeaders extends Command
{
    protected $signature = 'natura:testar {--categoria=perfumaria-feminina}';
    protected $description = 'Teste seco do BFF: imprime status, body snippet e headers enviados (sem salvar no BD)';

    public function handle()
    {
        $url        = env('NATURA_BFF_URL', 'https://www.natura.com.br/bff-app-natura-brazil/search');
        $bearer     = env('NATURA_BEARER', '');
        $tenantName = env('NATURA_TENANT_NAME', 'tenant_id');
        $tenantVal  = env('NATURA_TENANT_VALUE', 'NATBRA');
        $cookie     = env('NATURA_COOKIE', '');

        if (empty($bearer)) {
            $this->error('❌ NATURA_BEARER vazio no .env');
            return self::FAILURE;
        }

        $categoria = $this->option('categoria');

        $params = [
            'count'     => 24,
            'q'         => '',
            'expand'    => 'prices,availability,images,variations',
            'sort'      => 'top-sellers',
            'refine_1'  => 'cgid='.$categoria,
            'apiMode'   => 'product',
            'start'     => 0,
        ];

        // monta headers exatamente como navegador + bearer + tenant
        $headers = [
            'Accept'           => '*/*',
            'Accept-Language'  => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127 Safari/537.36',
            'Origin'           => 'https://www.natura.com.br',
            'Referer'          => 'https://www.natura.com.br/',
            'Authorization'    => $bearer,
            $tenantName        => $tenantVal, // <- nome do header vem do .env
        ];
        if ($cookie) $headers['Cookie'] = $cookie;

        $this->info('🔎 Headers que serão enviados:');
        foreach ($headers as $k => $v) {
            $shown = in_array($k, ['Authorization','Cookie']) ? '[definido]' : $v;
            $this->line("  - {$k}: {$shown}");
        }

        $this->line('➡️  Fazendo requisição...');
        $resp = Http::withOptions(['verify'=>false,'timeout'=>25])
            ->withHeaders($headers)
            ->get($url, $params);

        $this->info('HTTP status: '.$resp->status());
        $this->line('Body (primeiros 800 chars):');
        $this->line(mb_substr($resp->body(), 0, 800));

        return self::SUCCESS;
    }
}
