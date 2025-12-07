<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcompra', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->nullable()
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')
                ->on('appempresas')
                ->onDelete('cascade');
        });

        // Como hoje você já tem compras antigas, amarra tudo na empresa 1
        DB::table('appcompra')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('appcompra', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
