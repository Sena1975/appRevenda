<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appfornecedor', function (Blueprint $table) {
            // Remove o UNIQUE antigo do CNPJ (global)
            // Opção A (pelo nome do índice que apareceu no erro):
            $table->dropUnique('appfornecedor_cnpj_unique');

            // Opção B (se preferir por colunas, comente a linha de cima e use esta):
            // $table->dropUnique(['cnpj']);

            // Cria UNIQUE por empresa
            $table->unique(['empresa_id', 'cnpj'], 'appfornecedor_empresa_cnpj_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appfornecedor', function (Blueprint $table) {
            $table->dropUnique('appfornecedor_empresa_cnpj_unique');
            $table->unique('cnpj', 'appfornecedor_cnpj_unique');
        });
    }
};
