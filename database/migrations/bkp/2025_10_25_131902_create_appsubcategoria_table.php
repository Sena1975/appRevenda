<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appsubcategoria', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->foreignId('categoria_id')->constrained('appcategoria')->onDelete('cascade');
            $table->string('subcategoria', 100)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appsubcategoria');
    }
};
