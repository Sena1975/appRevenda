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
    Schema::create('appbaixa_pagar', function (Blueprint $table) {
        $table->increments('id');

        // Conta a pagar principal
        $table->integer('conta_id'); // appcontaspagar.id

        // Informações auxiliares (snapshot)
        $table->string('numero_nota', 50)->nullable();
        $table->integer('parcela');

        $table->date('data_baixa');
        $table->decimal('valor_baixado', 10, 2);

        // Aqui você usou texto em appbaixa_receber, vou manter igual
        $table->string('forma_pagamento', 50);

        $table->text('observacao')->nullable();

        $table->boolean('recibo_enviado')->default(false);

        // Mesmo padrão de appbaixa_receber
        $table->timestamp('criado_em')->useCurrent();

        $table->index('conta_id');
    });
}


    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::dropIfExists('appbaixa_pagar');
}

};
