<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewProduto;

class EstoqueController extends Controller
{
    public function index(Request $request)
{
    $query = ViewProduto::query();

    // Detecta se é a "primeira abertura" (sem qualquer parâmetro na query string)
    $primeiraAbertura = empty($request->query());

    // Se for primeira abertura, padrão = true
    // Senão, respeita o que veio no checkbox
    if ($primeiraAbertura) {
        $somenteComEstoque = true;
    } else {
        $somenteComEstoque = $request->boolean('somente_com_estoque');
    }

    // Filtro por produto (código ou descrição)
    if ($request->filled('produto')) {
        $busca = trim($request->produto);

        $query->where(function ($q) use ($busca) {
            $q->where('descricao_produto', 'like', "%{$busca}%")
              ->orWhere('codigo_fabrica', 'like', "%{$busca}%");
        });
    }

    // Apenas com estoque > 0 (já com padrão aplicando na primeira abertura)
    if ($somenteComEstoque) {
        $query->where('qtd_estoque', '>', 0);
    }

    // ---------- ORDENÇÃO (mantém igual ao que já fizemos) ----------
    $sort = $request->get('sort', 'descricao');      // padrão: descrição
    $dir  = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

    $columnMap = [
        'codigo'         => 'codigo_fabrica',
        'descricao'      => 'descricao_produto',
        'categoria'      => 'categoria',
        'estoque'        => 'qtd_estoque',
        'preco_compra'   => 'preco_compra',
        'preco_revenda'  => 'preco_revenda',
        'ultima_entrada' => 'data_ultima_entrada',
    ];

    $orderColumn = $columnMap[$sort] ?? 'descricao_produto';
    $query->orderBy($orderColumn, $dir);

    $itens = $query
        ->paginate(20)
        ->appends($request->query());

    return view('estoque.index', compact('itens', 'sort', 'dir', 'somenteComEstoque'));
}

}