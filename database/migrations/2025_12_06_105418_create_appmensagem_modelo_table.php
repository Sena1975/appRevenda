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
Schema::create('appmensagem_modelo', function (Blueprint $table) {
    $table->id();
    $table->string('codigo')->unique(); // ex.: 'boas_vindas_cliente'
    $table->string('nome');             // ex.: 'Boas-vindas para novo cliente'
    $table->string('canal')->default('whatsapp');
    $table->text('conteudo');           // texto com placeholders simples, se quiser
    $table->boolean('ativo')->default(true);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appmensagem_modelo');
    }
};
