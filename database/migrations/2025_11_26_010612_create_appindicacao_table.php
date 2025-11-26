<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appindicacao', function (Blueprint $table) {
            $table->id();

            // Quem indicou (cliente já cadastrado)
            $table->unsignedBigInteger('indicador_id');

            // Cliente novo (indicado)
            $table->unsignedBigInteger('indicado_id');

            // Pedido que gerou o prêmio (primeiro pedido ENTREGUE)
            $table->unsignedBigInteger('pedido_id');

            $table->decimal('valor_pedido', 10, 2)->default(0);
            $table->decimal('valor_premio', 10, 2)->default(0);

            // pendente = ainda não paguei o PIX
            // pago     = já paguei o prêmio
            $table->enum('status', ['pendente', 'pago'])->default('pendente');

            $table->dateTime('data_pagamento')->nullable();

            $table->timestamps();

            // Se quiser, pode descomentar as FKs abaixo
            // (garante integridade, mas pode dar erro se tentar excluir cliente/pedido com indicação)
            /*
            $table->foreign('indicador_id')
                  ->references('id')->on('appcliente');

            $table->foreign('indicado_id')
                  ->references('id')->on('appcliente');

            $table->foreign('pedido_id')
                  ->references('id')->on('apppedidovenda');
            */
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appindicacao');
    }
};
