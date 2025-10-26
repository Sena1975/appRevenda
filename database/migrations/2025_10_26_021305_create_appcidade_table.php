<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appcidade', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('codigoibge', 7)->nullable()->unique();
            $table->unsignedBigInteger('uf_id');
            $table->timestamps();

            // Cria o relacionamento com a tabela appuf
            $table->foreign('uf_id')
                ->references('id')
                ->on('appuf')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appcidade');
    }
};
