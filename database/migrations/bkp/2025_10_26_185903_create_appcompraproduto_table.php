<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appcompraproduto', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('produto_id');
            $table->decimal('quantidade', 10, 2)->default(0);
            $table->decimal('preco_unitario', 10, 2)->default(0);
            $table->decimal('total_item', 10, 2)->virtualAs('quantidade * preco_unitario');
            $table->decimal('pontos', 10, 2)->default(0);
            $table->decimal('pontostotal', 10, 2)->virtualAs('quantidade * pontos');
            $table->decimal('qtd_disponivel', 10, 2)->default(0);
            $table->timestamps();

            // Relacionamentos
            $table->foreign('compra_id')
                  ->references('id')
                  ->on('appcompra')
                  ->onDelete('cascade');

            $table->foreign('produto_id')
                  ->references('id')
                  ->on('appproduto')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appcompraproduto');
    }
};
