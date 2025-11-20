<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->string('origem_cadastro', 50)
                ->nullable()
                ->default('Interno') // padrão para cadastros feitos por você no sistema
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->dropColumn('origem_cadastro');
        });
    }
};
