<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcontasreceber', function (Blueprint $table) {
            // adiciona a coluna
            $table->unsignedBigInteger('empresa_id')
                ->nullable()
                ->after('revendedora_id');

            // se quiser FK (opcional agora; pode adicionar depois)
            $table->foreign('empresa_id')
                ->references('id')
                ->on('appempresas');
        });
    }

    public function down(): void
    {
        Schema::table('appcontasreceber', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
