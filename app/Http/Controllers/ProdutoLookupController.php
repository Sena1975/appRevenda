<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewProduto;

class ProdutoLookupController extends Controller
{
    public function buscar(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 15);
        $limit = $limit > 0 && $limit <= 50 ? $limit : 15;

        $query = ViewProduto::query()->select([
            'codigo_fabrica','descricao_produto','categoria','subcategoria',
            'preco_revenda','preco_compra','pontos','ciclo','qtd_estoque','data_ultima_entrada',
        ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('descricao_produto', 'like', "%{$q}%")
                  ->orWhere('codigo_fabrica', 'like', "%{$q}%");
            });
        }

        $itens = $query->orderBy('descricao_produto')->limit($limit)->get();

        return response()->json(
            $itens->map(fn ($p) => [
                'id'                 => $p->codigo_fabrica,
                'codigo_fabrica'     => $p->codigo_fabrica,
                'descricao'          => $p->descricao_produto,
                'categoria'          => $p->categoria,
                'subcategoria'       => $p->subcategoria,
                'preco_revenda'      => (float)$p->preco_revenda,
                'preco_compra'       => (float)$p->preco_compra,
                'pontos'             => (int)$p->pontos,
                'ciclo'              => $p->ciclo,
                'qtd_estoque'        => (int)$p->qtd_estoque,
                'data_ultima_entrada'=> optional($p->data_ultima_entrada)->format('Y-m-d'),
                'text'               => "{$p->descricao_produto} ({$p->codigo_fabrica})",
            ])
        );
    }
}
