<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appwhatsapp_config', function (Blueprint $table) {
            if (!Schema::hasColumn('appwhatsapp_config', 'origin_tag_id')) {
                $table->string('origin_tag_id', 100)
                      ->nullable()
                      ->after('provider'); // ajusta o "after" se quiser
            }
        });
    }

    public function down(): void
    {
        Schema::table('appwhatsapp_config', function (Blueprint $table) {
            if (Schema::hasColumn('appwhatsapp_config', 'origin_tag_id')) {
                $table->dropColumn('origin_tag_id');
            }
        });
    }
};
