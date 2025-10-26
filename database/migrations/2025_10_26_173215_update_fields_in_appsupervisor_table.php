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
    Schema::table('appsupervisor', function (Blueprint $table) {
        $table->string('instagram')->nullable()->after('email');
        $table->string('facebook')->nullable()->after('instagram');
        $table->string('telegram')->nullable()->after('facebook');
    });
}


    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::table('appsupervisor', function (Blueprint $table) {
        $table->dropColumn(['instagram', 'facebook', 'telegram']);
    });
}
};
