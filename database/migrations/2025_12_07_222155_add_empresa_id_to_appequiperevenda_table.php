<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appequiperevenda', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->nullable()
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')
                ->on('appempresas')
                ->onDelete('cascade');
        });

        // Tudo que já existe, por enquanto, amarra na empresa 1
        DB::table('appequiperevenda')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('appequiperevenda', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
