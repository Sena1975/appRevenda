<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparMovimentacoes extends Command
{
    protected $signature = 'app:limpar-movimentacoes';
    protected $description = 'Limpa todas as tabelas de movimentações para iniciar produção limpa';
    public function handle()
    {
        // ⚠️ Desativa verificação de foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tabelas = [
            'appbaixa_receber',
            'appcampanha_cliente',
            'appcampanha_cupom',
            'appcampanha_premio',
            'appcampanha_produto',
            'appcampanha_tipo',
            'appcampanha',
            'appcompraproduto',
            'appcompra',
            'appitemvenda',
            'appcontasreceber',
            'appcontaspagar',
            'appmovestoque',
            'appestoque',
            'apppedidovenda',
        ];

        foreach ($tabelas as $tabela) {
            try {
                DB::table($tabela)->truncate();
                $this->info("✅ Tabela {$tabela} limpa com sucesso.");
            } catch (\Exception $e) {
                $this->error("⚠️ Erro ao limpar {$tabela}: " . $e->getMessage());
            }
        }
        

        // ✅ Reativa as foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('✨ Todas as movimentações foram limpas com sucesso.');
        return Command::SUCCESS;
    }
}
