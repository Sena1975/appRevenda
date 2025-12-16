<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::table('information_schema.statistics')
            ->selectRaw('COUNT(*) as c')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->first();

        return ((int)($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        /**
         * ============================================================
         * 1) BACKFILL empresa_id (appestoque)
         * ============================================================
         */
        if (Schema::hasTable('appestoque') && Schema::hasTable('appproduto')
            && Schema::hasColumn('appestoque', 'empresa_id')
            && Schema::hasColumn('appestoque', 'produto_id')
            && Schema::hasColumn('appproduto', 'empresa_id')
        ) {
            // seta empresa_id pelo produto (se estiver NULL)
            DB::statement("
                UPDATE appestoque e
                JOIN appproduto p ON p.id = e.produto_id
                SET e.empresa_id = p.empresa_id
                WHERE e.empresa_id IS NULL
                  AND p.empresa_id IS NOT NULL
            ");
        }

        /**
         * ============================================================
         * 2) BACKFILL empresa_id (appmovestoque)
         * ============================================================
         */
        if (Schema::hasTable('appmovestoque') && Schema::hasColumn('appmovestoque', 'empresa_id')) {

            // (a) VENDA -> pega empresa_id do pedido de venda
            if (Schema::hasTable('apppedidovenda') && Schema::hasColumn('apppedidovenda', 'empresa_id')
                && Schema::hasColumn('appmovestoque', 'origem')
                && Schema::hasColumn('appmovestoque', 'origem_id')
            ) {
                DB::statement("
                    UPDATE appmovestoque m
                    JOIN apppedidovenda v ON v.id = m.origem_id
                    SET m.empresa_id = v.empresa_id
                    WHERE m.empresa_id IS NULL
                      AND m.origem = 'VENDA'
                      AND v.empresa_id IS NOT NULL
                ");
            }

            // (b) COMPRA / DEVOLUCAO -> tenta pegar de pedido de compra (se existir)
            // Ajuste o nome da tabela se o seu for diferente.
            if (Schema::hasTable('apppedidocompra') && Schema::hasColumn('apppedidocompra', 'empresa_id')
                && Schema::hasColumn('appmovestoque', 'origem')
                && Schema::hasColumn('appmovestoque', 'origem_id')
            ) {
                DB::statement("
                    UPDATE appmovestoque m
                    JOIN apppedidocompra c ON c.id = m.origem_id
                    SET m.empresa_id = c.empresa_id
                    WHERE m.empresa_id IS NULL
                      AND m.origem IN ('COMPRA','DEVOLUCAO')
                      AND c.empresa_id IS NOT NULL
                ");
            }

            // (c) fallback: tenta puxar do produto (se ainda sobrou NULL)
            if (Schema::hasTable('appproduto') && Schema::hasColumn('appproduto', 'empresa_id')
                && Schema::hasColumn('appmovestoque', 'produto_id')
            ) {
                DB::statement("
                    UPDATE appmovestoque m
                    JOIN appproduto p ON p.id = m.produto_id
                    SET m.empresa_id = p.empresa_id
                    WHERE m.empresa_id IS NULL
                      AND p.empresa_id IS NOT NULL
                ");
            }
        }

        /**
         * ============================================================
         * 3) CONSOLIDAR DUPLICADOS em appestoque por (empresa_id, produto_id)
         *    - soma estoque_gerencial/reservado/avaria
         *    - mantém 1 linha (menor id)
         * ============================================================
         */
        if (Schema::hasTable('appestoque')
            && Schema::hasColumn('appestoque', 'empresa_id')
            && Schema::hasColumn('appestoque', 'produto_id')
        ) {
            // Atualiza a linha "keep_id" com os valores agregados
            DB::statement("
                UPDATE appestoque e
                JOIN (
                    SELECT
                        empresa_id,
                        produto_id,
                        MIN(id) AS keep_id,
                        MAX(codfabnumero) AS codfabnumero,
                        SUM(estoque_gerencial) AS estoque_gerencial,
                        SUM(reservado) AS reservado,
                        SUM(avaria) AS avaria,
                        MAX(ultimo_preco_compra) AS ultimo_preco_compra,
                        MAX(ultimo_preco_venda) AS ultimo_preco_venda,
                        MAX(data_ultima_mov) AS data_ultima_mov
                    FROM appestoque
                    GROUP BY empresa_id, produto_id
                    HAVING COUNT(*) > 1
                ) agg ON agg.keep_id = e.id
                SET
                    e.codfabnumero = agg.codfabnumero,
                    e.estoque_gerencial = agg.estoque_gerencial,
                    e.reservado = agg.reservado,
                    e.avaria = agg.avaria,
                    e.ultimo_preco_compra = agg.ultimo_preco_compra,
                    e.ultimo_preco_venda = agg.ultimo_preco_venda,
                    e.data_ultima_mov = agg.data_ultima_mov,
                    e.updated_at = NOW()
            ");

            // Deleta as duplicadas (mantém keep_id)
            DB::statement("
                DELETE e FROM appestoque e
                JOIN (
                    SELECT empresa_id, produto_id, MIN(id) AS keep_id
                    FROM appestoque
                    GROUP BY empresa_id, produto_id
                    HAVING COUNT(*) > 1
                ) d ON d.empresa_id = e.empresa_id
                   AND d.produto_id = e.produto_id
                   AND e.id <> d.keep_id
            ");
        }

        /**
         * ============================================================
         * 4) FORÇAR empresa_id NOT NULL (appestoque / appmovestoque)
         * ============================================================
         */
        if (Schema::hasTable('appestoque') && Schema::hasColumn('appestoque', 'empresa_id')) {
            DB::statement("ALTER TABLE appestoque MODIFY empresa_id BIGINT UNSIGNED NOT NULL");
        }

        if (Schema::hasTable('appmovestoque') && Schema::hasColumn('appmovestoque', 'empresa_id')) {
            DB::statement("ALTER TABLE appmovestoque MODIFY empresa_id BIGINT UNSIGNED NOT NULL");
        }

        /**
         * ============================================================
         * 5) ÍNDICES
         * ============================================================
         */
        if (Schema::hasTable('appestoque')) {
            Schema::table('appestoque', function (Blueprint $table) {
                // nada aqui ainda; criamos no bloco abaixo pra checar existência
            });

            if (!$this->indexExists('appestoque', 'appestoque_empresa_produto_unique')) {
                Schema::table('appestoque', function (Blueprint $table) {
                    $table->unique(['empresa_id', 'produto_id'], 'appestoque_empresa_produto_unique');
                });
            }
        }

        if (Schema::hasTable('appmovestoque')) {
            if (!$this->indexExists('appmovestoque', 'appmovestoque_empresa_origem_status_idx')) {
                Schema::table('appmovestoque', function (Blueprint $table) {
                    $table->index(['empresa_id', 'origem', 'origem_id', 'status'], 'appmovestoque_empresa_origem_status_idx');
                });
            }

            if (!$this->indexExists('appmovestoque', 'appmovestoque_empresa_prod_data_idx')) {
                Schema::table('appmovestoque', function (Blueprint $table) {
                    $table->index(['empresa_id', 'produto_id', 'data_mov'], 'appmovestoque_empresa_prod_data_idx');
                });
            }
        }

        /**
         * ============================================================
         * 6) (Opcional) apppedidovenda.empresa_id NOT NULL
         *    Só força se não existir nenhum NULL (pra não quebrar).
         * ============================================================
         */
        if (Schema::hasTable('apppedidovenda') && Schema::hasColumn('apppedidovenda', 'empresa_id')) {
            $nulos = (int) DB::table('apppedidovenda')->whereNull('empresa_id')->count();

            if ($nulos === 0) {
                DB::statement("ALTER TABLE apppedidovenda MODIFY empresa_id BIGINT UNSIGNED NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Reversão segura: remove índices e volta empresa_id para NULLABLE (não desfaz consolidação)

        if (Schema::hasTable('appmovestoque')) {
            if ($this->indexExists('appmovestoque', 'appmovestoque_empresa_origem_status_idx')) {
                Schema::table('appmovestoque', function (Blueprint $table) {
                    $table->dropIndex('appmovestoque_empresa_origem_status_idx');
                });
            }

            if ($this->indexExists('appmovestoque', 'appmovestoque_empresa_prod_data_idx')) {
                Schema::table('appmovestoque', function (Blueprint $table) {
                    $table->dropIndex('appmovestoque_empresa_prod_data_idx');
                });
            }

            if (Schema::hasColumn('appmovestoque', 'empresa_id')) {
                DB::statement("ALTER TABLE appmovestoque MODIFY empresa_id BIGINT UNSIGNED NULL");
            }
        }

        if (Schema::hasTable('appestoque')) {
            if ($this->indexExists('appestoque', 'appestoque_empresa_produto_unique')) {
                Schema::table('appestoque', function (Blueprint $table) {
                    $table->dropUnique('appestoque_empresa_produto_unique');
                });
            }

            if (Schema::hasColumn('appestoque', 'empresa_id')) {
                DB::statement("ALTER TABLE appestoque MODIFY empresa_id BIGINT UNSIGNED NULL");
            }
        }

        if (Schema::hasTable('apppedidovenda') && Schema::hasColumn('apppedidovenda', 'empresa_id')) {
            DB::statement("ALTER TABLE apppedidovenda MODIFY empresa_id BIGINT UNSIGNED NULL");
        }
    }
};
