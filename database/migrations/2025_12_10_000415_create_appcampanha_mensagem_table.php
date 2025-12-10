<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appcampanha_mensagem', function (Blueprint $table) {
    $table->id();

    $table->integer('campanha_id'); // <- corrigido aqui

    $table->unsignedBigInteger('mensagem_modelo_id');
    $table->string('evento', 100);
    $table->integer('delay_minutos')->nullable();
    $table->boolean('ativo')->default(true);
    $table->json('condicoes')->nullable();

    $table->timestamps();

    $table->index(['campanha_id', 'evento', 'ativo'], 'idx_campanha_evento_ativo');

    $table->foreign('campanha_id')
          ->references('id')
          ->on('appcampanha')
          ->onDelete('cascade');

    $table->foreign('mensagem_modelo_id')
          ->references('id')
          ->on('appmensagem_modelo')
          ->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appcampanha_mensagem');
    }
};
