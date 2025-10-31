<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appproduto', function (Blueprint $table) {
            $table->id();
            $table->string('codfab', 50)->nullable();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();

            // Foreign keys - garantimos que referenciam tabelas já criadas
            $table->unsignedBigInteger('categoria_id');
            $table->unsignedBigInteger('subcategoria_id');
            $table->unsignedBigInteger('fornecedor_id');

            $table->foreign('categoria_id')->references('id')->on('appcategoria')->onDelete('cascade');
            $table->foreign('subcategoria_id')->references('id')->on('appsubcategoria')->onDelete('cascade');
            $table->foreign('fornecedor_id')->references('id')->on('appfornecedor')->onDelete('cascade');

            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appproduto');
    }
};
