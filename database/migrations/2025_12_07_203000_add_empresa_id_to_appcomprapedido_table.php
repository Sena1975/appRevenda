<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcompraproduto', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->nullable()
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')
                ->on('appempresas')
                ->onDelete('cascade');
        });

        // como hoje é só sua empresa, amarra tudo nela
        DB::table('appcompraproduto')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('appcompraproduto', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
