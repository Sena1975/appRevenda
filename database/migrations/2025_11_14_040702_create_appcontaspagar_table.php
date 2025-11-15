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
    Schema::create('appcontaspagar', function (Blueprint $table) {
        $table->increments('id');

        $table->string('numero_nota', 50)->nullable();
        $table->unsignedBigInteger('fornecedor_id'); // appfornecedor.id
        $table->integer('parcela');
        $table->integer('total_parcelas');
        $table->integer('forma_pagamento_id'); // appformapagamento.id

        $table->date('data_emissao');
        $table->date('data_vencimento');

        $table->decimal('valor', 10, 2);

        $table->enum('status', ['ABERTO', 'PAGO', 'CANCELADO'])->default('ABERTO');

        $table->date('data_pagamento')->nullable();
        $table->decimal('valor_pago', 10, 2)->nullable();

        $table->string('nosso_numero', 50)->nullable();
        $table->text('observacao')->nullable();

        // Mesmo padrão de appcontasreceber
        $table->timestamp('criado_em')->useCurrent();
        $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

        // Índices (iguais à sua sugestão de MUL)
        $table->index('fornecedor_id');
        $table->index('forma_pagamento_id');
        $table->index('status');
    });
}

    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::dropIfExists('appcontaspagar');
}

};
