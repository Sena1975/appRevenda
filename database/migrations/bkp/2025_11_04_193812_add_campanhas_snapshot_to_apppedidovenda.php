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
Schema::table('apppedidovenda', function (Blueprint $table) {
    $table->decimal('desconto_campanha', 10, 2)->default(0)->after('valor_total');
    $table->longText('campanhas_json')->nullable()->after('desconto_campanha');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apppedidovenda', function (Blueprint $table) {
            //
        });
    }
};
