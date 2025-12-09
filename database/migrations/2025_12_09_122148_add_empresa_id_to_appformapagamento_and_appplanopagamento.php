<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FORMAS DE PAGAMENTO
        Schema::table('appformapagamento', function (Blueprint $table) {
            if (!Schema::hasColumn('appformapagamento', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('empresa_id')
                    ->references('id')
                    ->on('appempresas');
            }
        });

        // PLANOS DE PAGAMENTO
        Schema::table('appplanopagamento', function (Blueprint $table) {
            if (!Schema::hasColumn('appplanopagamento', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('empresa_id')
                    ->references('id')
                    ->on('appempresas');
            }
        });

        // ⚠️ OPCIONAL: seta tudo como empresa 1 (empresa principal)
        // Ajuste se sua empresa "principal" tiver outro ID.
        DB::table('appformapagamento')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);

        DB::table('appplanopagamento')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('appformapagamento', function (Blueprint $table) {
            if (Schema::hasColumn('appformapagamento', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });

        Schema::table('appplanopagamento', function (Blueprint $table) {
            if (Schema::hasColumn('appplanopagamento', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
