<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appproduto', function (Blueprint $table) {
            // ajuste o "after" para uma coluna que exista aí na sua tabela
            $table->string('tipo', 1)
                ->default('P')
                ->comment('P = Produto unitário, K = Kit')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appproduto', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
