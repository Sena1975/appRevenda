<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o campo 'foto' na tabela appcliente.
     */
    public function up(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('email'); 
            // 👆 ajusta o "after" conforme o último campo que você tem na tabela
        });
    }

    /**
     * Remove o campo 'foto' (rollback).
     */
    public function down(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
