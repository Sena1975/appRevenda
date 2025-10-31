<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apptabelapreco', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('appproduto')->onDelete('cascade');
            $table->decimal('preco_revenda', 10, 2);
            $table->integer('pontuacao')->default(0);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apptabelapreco');
    }
};
