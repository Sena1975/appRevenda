<?php

// database/migrations/xxxx_xx_xx_add_metodo_php_to_appcampanha_tipo.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appcampanha_tipo', function (Blueprint $table) {
            $table->string('metodo_php', 100)->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('appcampanha_tipo', function (Blueprint $table) {
            $table->dropColumn('metodo_php');
        });
    }
};
