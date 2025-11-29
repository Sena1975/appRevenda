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
Schema::create('appmensagens', function (Blueprint $table) {
    $table->bigIncrements('id');

    // Relacionamentos de negócio (opcionais mas muito úteis)
    $table->unsignedBigInteger('cliente_id')->nullable();
    $table->unsignedBigInteger('pedido_id')->nullable();
    $table->unsignedBigInteger('campanha_id')->nullable();

    // Canal + direção
    $table->string('canal')->default('whatsapp'); // whatsapp, email, sms, etc.
    $table->string('direcao')->default('outbound'); // outbound = enviada, inbound = recebida

    // Tipo/propósito da mensagem (chave pra relatórios)
    $table->string('tipo')->nullable(); 
    // ex: 'boas_vindas_app', 'recibo_entrega', 'indicacao_pendente', 'indicacao_premio'

    // Conteúdo
    $table->text('conteudo');          // texto final enviado/recebido
    $table->json('payload')->nullable(); // extras (placeholders, dados do evento, etc.)

    // Dados do provedor (BotConversa)
    $table->string('provider')->default('botconversa');
    $table->string('provider_subscriber_id')->nullable();
    $table->string('provider_message_id')->nullable();
    $table->string('provider_status')->nullable(); // sent, delivered, failed, etc.

    // Status interno
    $table->string('status')->default('queued'); 
    // queued, sent, delivered, failed

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

};
