<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewProduto;
use App\Models\Produto; // <-- ADICIONE ISSO

class ProdutoLookupController extends Controller
{
    public function buscar(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 15);
        $limit = $limit > 0 && $limit <= 50 ? $limit : 15;

        $query = ViewProduto::query()->select([
            'codigo_fabrica',
            'descricao_produto',
            'categoria',
            'subcategoria',
            'preco_revenda',
            'preco_compra',
            'pontos',
            'ciclo',
            'qtd_estoque',
            'data_ultima_entrada',
        ]);

        if ($request->boolean('only_in_stock')) {
            $query->where('qtd_estoque', '>', 0);
        }
        
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('descricao_produto', 'like', "%{$q}%")
                    ->orWhere('codigo_fabrica', 'like', "%{$q}%");
            });
        }

        $itens = $query->orderBy('descricao_produto')->limit($limit)->get();

        return response()->json(
            $itens->map(function ($p) {
                $produtoId = \App\Models\Produto::where('codfabnumero', $p->codigo_fabrica)->value('id');

                return [
                    'id'                  => $p->codigo_fabrica, // usado pelo Select2
                    'codigo_fabrica'      => $p->codigo_fabrica,
                    'produto_id'          => $produtoId,
                    'descricao'           => $p->descricao_produto,
                    'categoria'           => $p->categoria,
                    'subcategoria'        => $p->subcategoria,
                    'preco_revenda'       => (float)$p->preco_revenda,
                    'preco_compra'        => (float)$p->preco_compra,
                    'pontos'              => (int)$p->pontos,
                    'ciclo'               => $p->ciclo,
                    'qtd_estoque'         => (int)$p->qtd_estoque,
                    'data_ultima_entrada' => optional($p->data_ultima_entrada)->format('Y-m-d'),
                    // AQUI: código + descrição
                    'text'                => "{$p->codigo_fabrica} - {$p->descricao_produto}",
                ];
            })
        );
    }
}
