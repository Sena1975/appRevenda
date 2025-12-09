<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 🔹 appestoque
        Schema::table('appestoque', function (Blueprint $table) {
            if (!Schema::hasColumn('appestoque', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('empresa_id')
                    ->references('id')
                    ->on('appempresas');
            }
        });

        // 🔹 appmovestoque
        Schema::table('appmovestoque', function (Blueprint $table) {
            if (!Schema::hasColumn('appmovestoque', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('empresa_id')
                    ->references('id')
                    ->on('appempresas');
            }
        });

        // 🔹 Backfill (ajuste se a empresa padrão não for 1)
        DB::table('appestoque')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);

        DB::table('appmovestoque')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('appestoque', function (Blueprint $table) {
            if (Schema::hasColumn('appestoque', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });

        Schema::table('appmovestoque', function (Blueprint $table) {
            if (Schema::hasColumn('appmovestoque', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
