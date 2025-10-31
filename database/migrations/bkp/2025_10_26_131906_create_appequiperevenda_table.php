<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::table('appequiperevenda', function (Blueprint $table) {
    //    $table->unsignedBigInteger('revendedora_id')->nullable()->after('nome');
        $table->foreign('revendedora_id')->references('id')->on('apprevendedora')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('appequiperevenda', function (Blueprint $table) {
        $table->dropForeign(['revendedora_id']);
        $table->dropColumn('revendedora_id');
    });
}

};
