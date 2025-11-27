<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o status "cancelado" ao enum.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `appindicacao`
            MODIFY `status`
                ENUM('pendente','pago','cancelado')
                COLLATE utf8mb4_unicode_ci
                NOT NULL
                DEFAULT 'pendente'
        ");
    }

    /**
     * Volta para apenas 'pendente' e 'pago'.
     * (antes, converte qualquer 'cancelado' para 'pendente')
     */
    public function down(): void
    {
        // Garante que não fiquem valores inválidos para o enum antigo
        DB::statement("
            UPDATE `appindicacao`
            SET `status` = 'pendente'
            WHERE `status` = 'cancelado'
        ");

        DB::statement("
            ALTER TABLE `appindicacao`
            MODIFY `status`
                ENUM('pendente','pago')
                COLLATE utf8mb4_unicode_ci
                NOT NULL
                DEFAULT 'pendente'
        ");
    }
};
