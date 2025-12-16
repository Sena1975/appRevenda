<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ViewProduto;
use App\Models\Produto;

class ProdutoLookupController extends Controller
{
    /**
     * Resolve empresa_id de forma segura:
     * 1) app('empresa') do middleware EmpresaAtiva
     * 2) usuário logado
     */
    private function empresaIdOrFail(): int
    {
        $empresaId = 0;

        if (app()->bound('empresa')) {
            $empresaId = (int) (app('empresa')->id ?? 0);
        }

        if ($empresaId <= 0) {
            $empresaId = (int) (Auth::user()?->empresa_id ?? 0);
        }

        if ($empresaId <= 0) {
            abort(403, 'Empresa ativa não definida.');
        }

        return $empresaId;
    }

    public function buscar(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 15);
        $limit = $limit > 0 && $limit <= 50 ? $limit : 15;

        $empresaId = (int) (app()->bound('empresa')
            ? (app('empresa')->id ?? 0)
            : (Auth::user()->empresa_id ?? 0));

        $query = ViewProduto::query()->select([
            'empresa_id',
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

        if ($empresaId > 0) {
            $query->where('empresa_id', $empresaId);
        }

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
            $itens->map(function ($p) use ($empresaId) {
                // importante: agora filtra também por empresa
                $produtoId = Produto::where('empresa_id', $empresaId)
                    ->where('codfabnumero', $p->codigo_fabrica)
                    ->value('id');

                return [
                    'id'             => $p->codigo_fabrica,
                    'codigo_fabrica' => $p->codigo_fabrica,
                    'produto_id'     => $produtoId,
                    'descricao'      => $p->descricao_produto,
                    'categoria'      => $p->categoria,
                    'subcategoria'   => $p->subcategoria,
                    'preco_revenda'  => (float)$p->preco_revenda,
                    'preco_compra'   => (float)$p->preco_compra,
                    'pontos'         => (int)$p->pontos,
                    'ciclo'          => $p->ciclo,
                    'qtd_estoque'    => (int)$p->qtd_estoque,
                    'data_ultima_entrada' => optional($p->data_ultima_entrada)->format('Y-m-d'),
                    'text'           => "{$p->codigo_fabrica} - {$p->descricao_produto}",
                ];
            })
        );
    }
}
