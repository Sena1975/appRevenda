<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appprodutonatura', function (Blueprint $table) {
            $table->id();

            // Dados principais
            $table->string('productId')->index();        // NATBRA-xxxx
            $table->string('name')->nullable();          // Nome comercial
            $table->string('friendlyName')->nullable();  // Nome curto
            $table->string('categoryId')->nullable();    // Ex: perfumaria-feminina
            $table->string('categoryName')->nullable();  // Ex: Perfumaria Feminina
            $table->string('classificationId')->nullable();
            $table->string('classificationName')->nullable();
            $table->text('description')->nullable();     // Descrição, se vier

            // Preços
            $table->decimal('price_sales_value', 10, 2)->nullable();
            $table->decimal('price_list_value', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();

            // Avaliações e tags
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('tags')->nullable();            // JSON com tags
            $table->text('images')->nullable();          // JSON com imagens
            $table->string('url')->nullable();           // URL do produto no site

            // JSON completo (pra referência futura)
            $table->json('raw_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appprodutonatura');
    }
};
