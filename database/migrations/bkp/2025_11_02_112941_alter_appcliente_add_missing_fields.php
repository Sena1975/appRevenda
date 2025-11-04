<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Para cada campo, só cria se não existir
        if (!Schema::hasColumn('appcliente', 'telefone')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('telefone', 20)->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('appcliente', 'cep')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('cep', 9)->nullable()->after('telefone');
            });
        }

        if (!Schema::hasColumn('appcliente', 'endereco')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('endereco', 255)->nullable()->after('cep');
            });
        }

        if (!Schema::hasColumn('appcliente', 'bairro')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('bairro', 100)->nullable()->after('endereco');
            });
        }

        if (!Schema::hasColumn('appcliente', 'cidade')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('cidade', 100)->nullable()->after('bairro');
            });
        }

        if (!Schema::hasColumn('appcliente', 'uf')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('uf', 2)->nullable()->after('cidade');
            });
        }

        if (!Schema::hasColumn('appcliente', 'instagram')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('instagram', 100)->nullable()->after('uf');
            });
        }

        if (!Schema::hasColumn('appcliente', 'facebook')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('facebook', 100)->nullable()->after('instagram');
            });
        }

        if (!Schema::hasColumn('appcliente', 'cpf')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('cpf', 14)->nullable()->after('facebook'); // ###.###.###-##
            });
        }

        if (!Schema::hasColumn('appcliente', 'data_nascimento')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->date('data_nascimento')->nullable()->after('cpf');
            });
        }

        if (!Schema::hasColumn('appcliente', 'sexo')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('sexo', 1)->nullable()->after('data_nascimento'); // M/F/O
            });
        }

        if (!Schema::hasColumn('appcliente', 'filhos')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->unsignedSmallInteger('filhos')->nullable()->default(0)->after('sexo');
            });
        }

        if (!Schema::hasColumn('appcliente', 'timecoracao')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('timecoracao', 60)->nullable()->after('filhos');
            });
        }

        if (!Schema::hasColumn('appcliente', 'status')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->default('Ativo')->after('timecoracao');
            });
        }

        if (!Schema::hasColumn('appcliente', 'foto')) {
            Schema::table('appcliente', function (Blueprint $table) {
                $table->string('foto', 255)->nullable()->after('status');
            });
        }

        // Índices úteis
        if (!Schema::hasColumn('appcliente', 'email')) {
            // se a coluna email não existia, não dá pra por unique aqui sem saber o tipo atual.
            // Mas geralmente já existe; se quiser garantir unique:
        }
        // Unique seguro (só se a coluna existir e ainda não houver índice):
        // OBS: Laravel não tem "hasIndex" nativo; se precisar, fazemos em passo separado.
    }

    public function down(): void
    {
        // Em "down" normalmente removeríamos as colunas,
        // mas para não arriscar perda de dados, vamos deixar em branco.
    }
};
