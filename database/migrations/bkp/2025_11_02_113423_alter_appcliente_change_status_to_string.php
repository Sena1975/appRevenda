<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Se status ainda for inteiro, converte para VARCHAR(20) com default 'Ativo'
        if (Schema::hasColumn('appcliente', 'status')) {
            // 1) caso existam 0/1, mapeia antes de trocar o tipo (opcional e seguro)
            // Ignora se a coluna já for texto.
            try {
                DB::statement("UPDATE appcliente SET status = CASE status WHEN '1' THEN 'Ativo' WHEN '0' THEN 'Inativo' ELSE status END");
            } catch (\Throwable $e) {
                // se já era texto, esse UPDATE não quebra; seguimos
            }

            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->default('Ativo')->change();
            });
        }
    }

    public function down(): void
    {
        // Voltar para inteiro (0/1). Só faça se realmente precisar.
        // Mapeia strings de volta e troca o tipo.
        try {
            DB::statement("UPDATE appcliente SET status = CASE LOWER(status) WHEN 'ativo' THEN '1' WHEN 'inativo' THEN '0' ELSE status END");
        } catch (\Throwable $e) {}

        Schema::table('appcliente', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->nullable()->default(1)->change();
        });
    }
};
