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
        Schema::create('appfornecedor', function (Blueprint $table) {
            $table->id();
            $table->string('razaosocial', 150);
            $table->string('nomefantasia', 150)->nullable();
            $table->string('cnpj', 20)->unique();
            $table->string('pessoacontato', 100)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('telegram', 50)->nullable();
            $table->string('instagram', 100)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('endereco', 200)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appfornecedor');
    }
};
