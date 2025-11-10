<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a limpeza das tabelas transacionais de teste.
     *
     * Atenção: isso APAGA TODOS OS DADOS dessas tabelas e reseta o AUTO_INCREMENT.
     */
    public function up(): void
    {
        // Liste aqui exatamente as tabelas que deseja truncar
        $tabelas = [
            'appbaixa_receber',
            'appcompraproduto',
            'appcompra',
            'appitemvenda',
            'appmovestoque',
            'appcontasreceber',
            'apppedidovenda',
            'appestoque',
        ];

        // Desabilita FKs para permitir TRUNCATE em qualquer ordem
        Schema::disableForeignKeyConstraints();

        foreach ($tabelas as $tabela) {
            // Usa TRUNCATE explícito para garantir reset de AUTO_INCREMENT
            DB::statement('TRUNCATE TABLE `' . $tabela . '`');
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Não há rollback de dados truncados.
     */
    public function down(): void
    {
        // Intencionalmente sem ação (não há como restaurar dados truncados)
    }
};
