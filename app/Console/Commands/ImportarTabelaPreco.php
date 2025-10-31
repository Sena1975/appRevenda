<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppProduto;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class ImportarTabelaPreco extends Command
{
    protected $signature = 'tabela:importar-precos';
    protected $description = 'Popula a tabela apptabelapreco com os dados de preço da appproduto.';

    public function handle()
    {
        $this->info('🔄 Iniciando importação de preços para a tabela APPTABELAPRECO...');

        // Limpa a tabela antes de preencher (opcional)
        DB::table('apptabelapreco')->truncate();

        // Datas fixas do ciclo
        $dataInicio = '2025-10-24';
        $dataFim = '2025-11-13';

        $produtos = Produto::all();
        $total = 0;


        foreach ($produtos as $produto) {
            DB::table('apptabelapreco')->insert([
                'codfab'        => $produto->codfabnumero,
                'produto_id'    => $produto->id, // 🔹 usa o ID numérico da tabela appproduto
                'preco_revenda' => $produto->preco_revenda ?? 0,
                'preco_compra'  => $produto->preco_compra ?? 0,
                'pontuacao'     => 0,
                'data_inicio'   => $dataInicio,
                'data_fim'      => $dataFim,
                'status'        => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $total++;
        }

        $this->info("✅ Importação concluída com sucesso!");
        $this->info("📦 {$total} registros inseridos na tabela APPTABELAPRECO.");
    }
}
