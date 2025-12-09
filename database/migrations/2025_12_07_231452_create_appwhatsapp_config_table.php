<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appwhatsapp_config', function (Blueprint $table) {
            $table->id();

            // empresa dona dessa conexão
            $table->unsignedBigInteger('empresa_id');

            // ex: botconversa, zapi, etc.
            $table->string('provider', 50);

            // número do WhatsApp (somente dígitos ou com +55)
            $table->string('phone_number', 30)->nullable();

            // opcional: nome pra exibir no painel (ex.: "WhatsApp Vendas")
            $table->string('nome_exibicao', 100)->nullable();

            // credenciais genéricas
            $table->string('api_url', 255)->nullable();
            $table->string('api_key', 255)->nullable();
            $table->string('token', 255)->nullable();
            $table->string('instance_id', 255)->nullable();

            // status / se é a conexão padrão da empresa
            $table->boolean('is_default')->default(false);
            $table->boolean('ativo')->default(true);

            // qualquer coisa extra (json)
            $table->json('extras')->nullable();

            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id')
                ->on('appempresas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appwhatsapp_config');
    }
};
