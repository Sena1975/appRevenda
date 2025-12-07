<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appempresas', function (Blueprint $table) {
            $table->id();

            $table->string('nome_fantasia');
            $table->string('razao_social')->nullable();

            // CPF ou CNPJ. Você pode especializar depois.
            $table->string('documento', 20)->nullable();

            $table->string('email_contato')->nullable();
            $table->string('telefone', 20)->nullable();

            // Para no futuro usar subdomínios ou URLs amigáveis, se quiser
            $table->string('slug')->unique();

            // Plano do sistema (para monetização futura)
            $table->string('plano')->default('basico');

            // Se a empresa está ativa ou não
            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appempresas');
    }
};
