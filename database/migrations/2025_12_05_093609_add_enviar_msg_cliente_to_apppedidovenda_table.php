<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('apppedidovenda', function (Blueprint $table) {
            $table->boolean('enviar_msg_cliente')
                  ->default(true)
                  ->after('valor_liquido'); // ajuste a coluna 'after' se quiser
        });
    }

    public function down(): void
    {
        Schema::table('apppedidovenda', function (Blueprint $table) {
            $table->dropColumn('enviar_msg_cliente');
        });
    }
};
