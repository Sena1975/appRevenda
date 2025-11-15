<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appcompra', function (Blueprint $table) {
            // IDs das tabelas de formas/plano de pagamento
            $table->unsignedBigInteger('forma_pagamento_id')->nullable()->after('formapgto');
            $table->unsignedBigInteger('plano_pagamento_id')->nullable()->after('forma_pagamento_id');

            $table->index('forma_pagamento_id');
            $table->index('plano_pagamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('appcompra', function (Blueprint $table) {
            $table->dropIndex(['forma_pagamento_id']);
            $table->dropIndex(['plano_pagamento_id']);

            $table->dropColumn('forma_pagamento_id');
            $table->dropColumn('plano_pagamento_id');
        });
    }
};

