<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::table('apprevendedora', function (Blueprint $table) {
    if (!Schema::hasColumn('apprevendedora', 'cep')) {
        $table->string('cep', 9)->nullable()->after('cpf');
    }
    if (!Schema::hasColumn('apprevendedora', 'endereco')) {
        $table->string('endereco')->nullable()->after('cep');
    }
    if (!Schema::hasColumn('apprevendedora', 'bairro')) {
        $table->string('bairro')->nullable()->after('endereco');
    }
    if (!Schema::hasColumn('apprevendedora', 'cidade')) {
        $table->string('cidade')->nullable()->after('bairro');
    }
    if (!Schema::hasColumn('apprevendedora', 'estado')) {
        $table->string('estado', 2)->nullable()->after('cidade');
    }
    if (!Schema::hasColumn('apprevendedora', 'status')) {
        $table->boolean('status')->default(true);
    }
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprevendedora', function (Blueprint $table) {
            //
        });
    }
};
