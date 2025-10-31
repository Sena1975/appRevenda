<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appprodutonatura', function (Blueprint $table) {
            // alguns ambientes não aceitam "after", então deixei sem
            if (!Schema::hasColumn('appprodutonatura', 'categoria_slug')) {
                $table->string('categoria_slug', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('appprodutonatura', 'ciclo_data')) {
                $table->string('ciclo_data', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('appprodutonatura', 'datainclusao')) {
                // default NOW(); em MySQL/MariaDB
                $table->dateTime('datainclusao')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appprodutonatura', function (Blueprint $table) {
            if (Schema::hasColumn('appprodutonatura', 'categoria_slug')) {
                $table->dropColumn('categoria_slug');
            }
            if (Schema::hasColumn('appprodutonatura', 'ciclo_data')) {
                $table->dropColumn('ciclo_data');
            }
            if (Schema::hasColumn('appprodutonatura', 'datainclusao')) {
                $table->dropColumn('datainclusao');
            }
        });
    }
};
