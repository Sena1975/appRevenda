<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appindicacao', function (Blueprint $table) {
            if (!Schema::hasColumn('appindicacao', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('empresa_id')
                    ->references('id')
                    ->on('appempresas')
                    ->onDelete('cascade');
            }
        });

        // Se já existir registro antigo, opcionalmente marca tudo como empresa 1
        if (Schema::hasColumn('appindicacao', 'empresa_id')) {
            DB::table('appindicacao')
                ->whereNull('empresa_id')
                ->update(['empresa_id' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('appindicacao', function (Blueprint $table) {
            if (Schema::hasColumn('appindicacao', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
