<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('appsupervisor', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('cpf', 14)->nullable();
        $table->string('telefone', 20)->nullable();
        $table->string('whatsapp', 20)->nullable();
        $table->string('email')->nullable();
        $table->string('cep', 9)->nullable();
        $table->string('endereco')->nullable();
        $table->string('bairro')->nullable();
        $table->string('cidade')->nullable();
        $table->string('estado', 2)->nullable();
        $table->boolean('status')->default(true);
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('appsupervisor');
    }
};
