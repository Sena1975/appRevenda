<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apprevendedora', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('cpf', 20)->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('telegram', 50)->nullable();
            $table->string('instagram', 100)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('endereco', 200)->nullable();

            // Relacionamentos
            $table->unsignedBigInteger('equipe_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();

            $table->foreign('equipe_id')
                ->references('id')
                ->on('appequiperevenda')
                ->onDelete('set null');

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('appsupervisor')
                ->onDelete('set null');

            $table->date('datanascimento')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apprevendedora');
    }
};
