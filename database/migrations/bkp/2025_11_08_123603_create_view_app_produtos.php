<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Derruba versões antigas da view (maiúscula e minúscula)
        DB::unprepared('DROP VIEW IF EXISTS VIEW_APP_PRODUTOS');
        DB::unprepared('DROP VIEW IF EXISTS view_app_produtos');

        DB::unprepared(<<<'SQL'
create or replace view view_app_produtos as
SELECT
    P.CODFABNUMERO AS codigo_fabrica,
    P.NOME         AS descricao_produto,
    C.NOME         AS categoria,
    SC.NOME        AS subcategoria,
    IFNULL(P.PRECO_REVENDA, 0) AS preco_revenda,
    IFNULL(P.PRECO_COMPRA, 0)  AS preco_compra,
    IFNULL(TP.PONTUACAO, 0)    AS pontos,
    PN.CICLO_DATA              AS ciclo,
    IFNULL(E.DISPONIVEL, 0)    AS qtd_estoque,
    (
        SELECT MAX(M.DATA_MOV)
        FROM APPMOVESTOQUE M
        WHERE M.CODFABNUMERO = P.CODFABNUMERO
          AND M.TIPO_MOV = 'ENTRADA'
          AND M.ORIGEM  = 'COMPRA'
    ) AS data_ultima_entrada
FROM APPPRODUTO P
LEFT JOIN APPCATEGORIA       C  ON P.CATEGORIA_ID    = C.ID
LEFT JOIN APPSUBCATEGORIA    SC ON P.SUBCATEGORIA_ID = SC.ID
LEFT JOIN APPTABELAPRECO     TP ON TP.CODFAB         = P.CODFABNUMERO
LEFT JOIN APPPRODUTONATURA   PN ON PN.CODFABNUMERO   = P.CODFABNUMERO
LEFT JOIN APPESTOQUE         E  ON E.CODFABNUMERO    = P.CODFABNUMERO;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS view_app_produtos');
        // (opcional) também derruba a antiga maiúscula
        DB::unprepared('DROP VIEW IF EXISTS VIEW_APP_PRODUTOS');
    }
};
