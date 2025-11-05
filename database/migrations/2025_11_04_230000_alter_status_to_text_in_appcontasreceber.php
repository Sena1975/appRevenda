<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Descobre todos os índices que usam a coluna `status` e remove
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appcontasreceber'
              AND COLUMN_NAME = 'status'
        ");

        foreach ($indexes as $idx) {
            $name = $idx->INDEX_NAME;
            // Não tente dropar a PRIMARY
            if (strtoupper($name) !== 'PRIMARY') {
                try {
                    DB::statement("ALTER TABLE appcontasreceber DROP INDEX `{$name}`");
                } catch (\Throwable $e) {
                    // ignora se já caiu antes / não existir
                }
            }
        }

        // Agora altera o tipo para VARCHAR(30) NOT NULL (em vez de TEXT para evitar limite de chave)
        DB::statement("ALTER TABLE appcontasreceber MODIFY COLUMN `status` VARCHAR(30) NOT NULL");

        // (Opcional) Se quiser manter um índice para buscas por status, descomente a linha abaixo:
        // DB::statement("ALTER TABLE appcontasreceber ADD INDEX `appcontasreceber_status_index` (`status`)");
    }

    public function down(): void
    {
        // Antes de voltar para ENUM, remova possíveis índices
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appcontasreceber'
              AND COLUMN_NAME = 'status'
        ");

        foreach ($indexes as $idx) {
            $name = $idx->INDEX_NAME;
            if (strtoupper($name) !== 'PRIMARY') {
                try {
                    DB::statement("ALTER TABLE appcontasreceber DROP INDEX `{$name}`");
                } catch (\Throwable $e) {
                    // ignora
                }
            }
        }

        // Volta para ENUM (com default 'ABERTO')
        DB::statement("
            ALTER TABLE appcontasreceber
            MODIFY COLUMN `status` ENUM('ABERTO','PAGO','CANCELADO') NOT NULL DEFAULT 'ABERTO'
        ");

        // Recria um índice simples como estava antes (se quiser)
        try {
            DB::statement("ALTER TABLE appcontasreceber ADD INDEX `appcontasreceber_status_index` (`status`)");
        } catch (\Throwable $e) {
            // ignora
        }
    }
};
