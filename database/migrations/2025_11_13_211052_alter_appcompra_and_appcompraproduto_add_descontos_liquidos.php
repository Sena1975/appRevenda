<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ITENS: tabela appcompraproduto
        Schema::table('appcompraproduto', function (Blueprint $table) {
            // valor_desconto logo após preco_unitario
            $table->decimal('valor_desconto', 10, 2)
                ->default(0)
                ->after('preco_unitario');

            // total_liquido após total_item
            $table->decimal('total_liquido', 10, 2)
                ->default(0)
                ->after('total_item');
        });

        // CABEÇALHO: tabela appcompra
        Schema::table('appcompra', function (Blueprint $table) {
            // valor_desconto e valor_liquido após valor_total
            $table->decimal('valor_desconto', 10, 2)
                ->default(0)
                ->after('valor_total');

            $table->decimal('valor_liquido', 10, 2)
                ->default(0)
                ->after('valor_desconto');

            // observacao antes do status (status vem logo depois de qt_parcelas)
            $table->text('observacao')
                ->nullable()
                ->after('qt_parcelas');
        });
    }

    public function down(): void
    {
        Schema::table('appcompraproduto', function (Blueprint $table) {
            $table->dropColumn(['valor_desconto', 'total_liquido']);
        });

        Schema::table('appcompra', function (Blueprint $table) {
            $table->dropColumn(['valor_desconto', 'valor_liquido', 'observacao']);
        });
    }
};
