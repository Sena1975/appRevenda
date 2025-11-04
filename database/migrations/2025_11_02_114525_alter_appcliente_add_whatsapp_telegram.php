<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('appcliente', 'whatsapp')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('whatsapp', 20)->nullable()->after('telefone'); // só números
            });
        }

        if (!Schema::hasColumn('appcliente', 'telegram')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('telegram', 64)->nullable()->after('whatsapp'); // username sem @
            });
        }
    }

    public function down(): void
    {
        // Remover (apenas se precisar reverter)
        if (Schema::hasColumn('appcliente', 'telegram')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->dropColumn('telegram');
            });
        }
        if (Schema::hasColumn('appcliente', 'whatsapp')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->dropColumn('whatsapp');
            });
        }
    }
};
