<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppProdutoNatura;
use App\Models\Categoria;
use App\Models\ProdutoNatura;
use App\Models\Subcategoria;

class ImportarCategoriasNatura extends Command
{
    protected $signature = 'natura:importar-categorias';
    protected $description = 'Importa categorias e subcategorias a partir da tabela appprodutonatura.';

    public function handle()
    {
        $this->info('🔄 Importando categorias e subcategorias da Natura...');

        $produtos = ProdutoNatura::select('classificationId', 'classificationName', 'categoryId', 'categoryName')
            ->distinct()
            ->get();

        $countCat = 0;
        $countSub = 0;

        foreach ($produtos as $p) {
            if (!$p->classificationId || !$p->classificationName) {
                continue; // ignora registros sem dados de categoria
            }

            // 1️⃣ Categoria
            $categoria = Categoria::firstOrCreate(
                ['categoria' => $p->classificationId],
                [
                    'nome' => $p->classificationName,
                    'status' => 1
                ]
            );
            if ($categoria->wasRecentlyCreated) $countCat++;

            // 2️⃣ Subcategoria
            if ($p->categoryId && $p->categoryName) {
                Subcategoria::firstOrCreate(
                    ['subcategoria' => $p->categoryId],
                    [
                        'nome' => $p->categoryName,
                        'categoria_id' => $categoria->id,
                        'status' => 1
                    ]
                );
                $countSub++;
            }
        }

        $this->info("✅ Importação concluída!");
        $this->info("📂 Categorias criadas: {$countCat}");
        $this->info("📂 Subcategorias criadas: {$countSub}");
    }
}
