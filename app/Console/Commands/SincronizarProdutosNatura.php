<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppProduto;
use App\Models\AppProdutoNatura;
use App\Models\AppCategoria;
use App\Models\AppSubcategoria;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoNatura;
use App\Models\Subcategoria;

class SincronizarProdutosNatura extends Command
{
    protected $signature = 'natura:sincronizar-produtos';
    protected $description = 'Insere todos os produtos da Natura (tabela appprodutonatura) na tabela principal appproduto com vínculo de categoria e subcategoria.';

    public function handle()
    {
        $this->info('🔄 Iniciando importação completa dos produtos Natura...');

        $produtos = ProdutoNatura::all();
        $total = 0;

        foreach ($produtos as $p) {
            $codigoCompleto = $p->productId;
            $codTexto = preg_replace('/[^A-Z]/i', '', $codigoCompleto);
            $codNumero = preg_replace('/\D/', '', $codigoCompleto);

            // 🔎 Localiza categoria e subcategoria
            $categoria = Categoria::where('categoria', $p->classificationId)->first();
            $subcategoria = Subcategoria::where('subcategoria', $p->categoryId)->first();

            // 🧩 Define IDs padrão se não encontrar
            $categoria_id = $categoria ? $categoria->id : 11;
            $subcategoria_id = $subcategoria ? $subcategoria->id : 83;

            // Evita duplicação
            if (!Produto::where('codfab', $codigoCompleto)->exists()) {
                Produto::create([
                    'codfab'          => $codigoCompleto,
                    'codfabtexto'     => $codTexto,
                    'codfabnumero'    => $codNumero,
                    'nome'            => $p->name,
                    'descricao'       => $p->friendlyName,
                    'imagem'          => $this->getFirstImage($p->images),
                    'categoria_id'    => $categoria_id,
                    'subcategoria_id' => $subcategoria_id,
                    'fornecedor_id'   => 1, // Natura
                    'status'          => 1,
                    'preco_revenda'   => $p->price_list_value ?? 0,
                    'preco_compra'    => $p->price_sales_value ?? 0,
                ]);

                $total++;
            }
        }

        $this->info("✅ Importação concluída! {$total} produtos inseridos na tabela appproduto.");
    }

    /**
     * Extrai a primeira imagem do JSON retornado.
     */
    private function getFirstImage($json)
    {
        if (empty($json)) {
            return null;
        }

        $data = json_decode($json, true);

        if (is_array($data)) {
            $first = reset($data);
            if (isset($first['url'])) {
                return $first['url'];
            }
        }

        return null;
    }
}
