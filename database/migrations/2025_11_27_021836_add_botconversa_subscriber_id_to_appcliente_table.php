<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->string('botconversa_subscriber_id')
                  ->nullable()
                  ->after('whatsapp'); // ou depois de algum campo de contato
        });
    }

    public function down(): void
    {
        Schema::table('appcliente', function (Blueprint $table) {
            $table->dropColumn('botconversa_subscriber_id');
        });
    }
};
