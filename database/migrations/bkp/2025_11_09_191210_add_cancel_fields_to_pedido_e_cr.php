<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // PEDIDO
        Schema::table('apppedidovenda', function (Blueprint $table) {
            if (!Schema::hasColumn('apppedidovenda', 'obs_cancelamento')) {
                $table->text('obs_cancelamento')->nullable();
            }
            if (!Schema::hasColumn('apppedidovenda', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable();
            }
        });

        // CONTAS A RECEBER
        Schema::table('appcontasreceber', function (Blueprint $table) {
            if (!Schema::hasColumn('appcontasreceber', 'obs_cancelamento')) {
                $table->text('obs_cancelamento')->nullable();
            }
            if (!Schema::hasColumn('appcontasreceber', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('apppedidovenda', function (Blueprint $table) {
            if (Schema::hasColumn('apppedidovenda', 'obs_cancelamento')) {
                $table->dropColumn('obs_cancelamento');
            }
            if (Schema::hasColumn('apppedidovenda', 'canceled_at')) {
                $table->dropColumn('canceled_at');
            }
        });

        Schema::table('appcontasreceber', function (Blueprint $table) {
            if (Schema::hasColumn('appcontasreceber', 'obs_cancelamento')) {
                $table->dropColumn('obs_cancelamento');
            }
            if (Schema::hasColumn('appcontasreceber', 'canceled_at')) {
                $table->dropColumn('canceled_at');
            }
        });
    }
};
