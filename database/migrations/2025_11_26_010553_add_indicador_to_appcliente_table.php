<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            // Cliente que fez a indicação
            // 1 = "vendedor" (sem prêmio)
            $table->unsignedBigInteger('indicador_id')
                ->default(1)
                ->after('id')
                ->comment('Cliente que indicou. 1 = vendedor (sem prêmio).');
        });
    }

    public function down(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->dropColumn('indicador_id');
        });
    }
};
