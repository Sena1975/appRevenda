<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimparBaseTeste extends Command
{
    use ConfirmableTrait;

    /**
     * Nome do comando no Artisan.
     */
    protected $signature = 'app:limpar-base-teste
                            {--force : Força a execução mesmo em ambiente de produção}';

    /**
     * Descrição do comando.
     */
    protected $description = 'Limpa dados de movimentação (compras, vendas, financeiro, estoque), mantendo cadastros.';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        // Proteção para ambiente de produção
        if (! $this->confirmToProceed()) {
            $this->info('Operação cancelada.');
            return Command::SUCCESS;
        }

        $this->info('Iniciando limpeza de movimentações...');

        // Tabelas de movimentação que queremos esvaziar
        $tables = [
            // Financeiro
            'appbaixa_receber',
            'appcontasreceber',
            'appbaixa_pagar',
            'appcontaspagar',

            // Vendas
            'appitemvenda',
            'apppedidovenda',

            // Compras
            'appcompraproduto',
            'appcompra',

            // Estoque
            'appmovestoque',
            'appestoque',

            // Campanhas (se existirem)
            'appcampanha_cliente',
            'appcampanha_cupom',
            'appcampanha_premio',
            'appcampanha_produto',
            'appcampanha',

            // Filas / sessões (opcional)
            'jobs',
            'failed_jobs',
            'sessions',
        ];

        DB::beginTransaction();

        try {
            // Desliga verificação de FK pra não dar erro em TRUNCATE
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->line("→ Truncando tabela: {$table}");
                    DB::table($table)->truncate();
                } else {
                    $this->line("→ Tabela não encontrada (ignorando): {$table}");
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            $this->info('✅ Limpeza concluída com sucesso.');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            // tenta religar FK mesmo em erro
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e2) {
                // ignora
            }

            $this->error('❌ Erro ao limpar base: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
//COMANDO PRA EXECUTAR ESSE SCRIPT
//php artisan app:limpar-base-teste --force