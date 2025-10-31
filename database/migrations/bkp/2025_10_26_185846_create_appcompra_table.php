<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appcompra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fornecedor_id');
            $table->date('data_compra')->nullable();
            $table->date('data_emissao')->nullable();
            $table->string('numpedcompra', 50)->nullable();
            $table->string('numero_nota', 50)->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->decimal('pontostotal', 10, 2)->default(0);
            $table->integer('qtditens')->default(0);
            $table->string('formapgto', 50)->nullable();
            $table->integer('qt_parcelas')->default(1);
            $table->enum('status', ['PENDENTE', 'RECEBIDA', 'CANCELADA'])->default('PENDENTE');
            $table->timestamps();

            $table->foreign('fornecedor_id')
                  ->references('id')
                  ->on('appfornecedor')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appcompra');
    }
};
