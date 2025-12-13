<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =========================
        // appcategoria
        // =========================
        Schema::table('appcategoria', function (Blueprint $table) {
            if (!Schema::hasColumn('appcategoria', 'empresa_id')) {
                $table->foreignId('empresa_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('appempresas')
                    ->cascadeOnDelete();

                $table->index('empresa_id', 'appcategoria_empresa_id_idx');
            }
        });

        // =========================
        // appsubcategoria
        // =========================
        Schema::table('appsubcategoria', function (Blueprint $table) {
            if (!Schema::hasColumn('appsubcategoria', 'empresa_id')) {
                $table->foreignId('empresa_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('appempresas')
                    ->cascadeOnDelete();

                $table->index('empresa_id', 'appsubcategoria_empresa_id_idx');
            }
        });

        // (Opcional, mas recomendado) Preencher registros antigos com uma empresa padrão
        // Para não ficar NULL nos dados existentes.
        $empresaPadraoId = DB::table('appempresas')->min('id');

        if ($empresaPadraoId) {
            DB::table('appcategoria')->whereNull('empresa_id')->update(['empresa_id' => $empresaPadraoId]);
            DB::table('appsubcategoria')->whereNull('empresa_id')->update(['empresa_id' => $empresaPadraoId]);
        }
    }

    public function down(): void
    {
        // appsubcategoria
        Schema::table('appsubcategoria', function (Blueprint $table) {
            if (Schema::hasColumn('appsubcategoria', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex('appsubcategoria_empresa_id_idx');
                $table->dropColumn('empresa_id');
            }
        });

        // appcategoria
        Schema::table('appcategoria', function (Blueprint $table) {
            if (Schema::hasColumn('appcategoria', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex('appcategoria_empresa_id_idx');
                $table->dropColumn('empresa_id');
            }
        });
    }
};
