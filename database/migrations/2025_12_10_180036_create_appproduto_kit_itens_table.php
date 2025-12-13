<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appproduto_kit_itens', function (Blueprint $table) {
            $table->id();

            // Produto que é o KIT (tipo = 'K')
            $table->unsignedBigInteger('kit_produto_id');

            // Produto unitário que compõe o kit (tipo = 'P')
            $table->unsignedBigInteger('item_produto_id');

            // Quantidade desse item dentro do kit
            $table->decimal('quantidade', 10, 3)->default(1);

            $table->timestamps();

            $table->foreign('kit_produto_id')
                ->references('id')
                ->on('appproduto')
                ->onDelete('cascade');

            $table->foreign('item_produto_id')
                ->references('id')
                ->on('appproduto')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appproduto_kit_itens');
    }
};
