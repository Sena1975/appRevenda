<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appequiperevenda', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();

            // Relacionamento com supervisor
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->foreign('supervisor_id')
                ->references('id')
                ->on('appsupervisor')
                ->onDelete('set null');

            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appequiperevenda');
    }
};
