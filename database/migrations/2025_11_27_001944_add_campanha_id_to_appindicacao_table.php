<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appindicacao', function (Blueprint $table) {
            // cria a coluna como INT normal, compatível com appcampanha.id
            $table->integer('campanha_id')
                ->nullable()
                ->after('pedido_id');

            $table->foreign('campanha_id')
                ->references('id')
                ->on('appcampanha')
                ->onDelete('set null'); // ou ->nullOnDelete() no Laravel 10+
        });
    }

    public function down(): void
    {
        Schema::table('appindicacao', function (Blueprint $table) {
            $table->dropForeign(['campanha_id']);
            $table->dropColumn('campanha_id');
        });
    }
};
