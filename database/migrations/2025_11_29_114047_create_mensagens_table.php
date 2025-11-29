<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appmensagens', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relacionamentos de negócio
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('campanha_id')->nullable();

            // Canal + direção
            $table->string('canal')->default('whatsapp');      // whatsapp, email...
            $table->string('direcao')->default('outbound');    // outbound = enviada, inbound = recebida

            // Tipo/propósito da mensagem (pra relatórios)
            $table->string('tipo')->nullable();                // ex: recibo_entrega, indicacao_pendente, indicacao_premio_pix

            // Conteúdo
            $table->text('conteudo');                          // texto enviado/recebido
            $table->json('payload')->nullable();               // dados extras (opcional)

            // Dados do provedor (BotConversa)
            $table->string('provider')->default('botconversa');
            $table->string('provider_subscriber_id')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('provider_status')->nullable();     // sent, delivered, failed, etc.

            // Status interno
            $table->string('status')->default('queued');       // queued, sent, delivered, failed

            // Datas
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index('cliente_id');
            $table->index('pedido_id');
            $table->index('campanha_id');
            $table->index(['tipo', 'canal']);
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appmensagens');
    }
};
