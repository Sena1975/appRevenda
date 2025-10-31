<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appproduto', function (Blueprint $table) {
            // Novo campo textual (parte antes dos números do código)
            $table->string('codfabtexto', 50)->nullable()->after('codfab');

            // Novo campo numérico (somente números do código)
            $table->bigInteger('codfabnumero')->nullable()->after('codfabtexto');
        });
    }

    public function down(): void
    {
        Schema::table('appproduto', function (Blueprint $table) {
            $table->dropColumn(['codfabtexto', 'codfabnumero']);
        });
    }
};
