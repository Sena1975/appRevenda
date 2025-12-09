<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ViewProduto;
use Illuminate\Support\Facades\Auth;

class EstoqueController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'descricao');
        $dir  = $request->get('dir', 'asc');

        // 'todos' (default), 'com', 'sem', 'zero'
        $filtroEstoque = $request->get('filtro_estoque', 'com');

        // 🔹 Descobre empresa do usuário / middleware
        $user    = Auth::user();
        $empresa = $user?->empresa;

        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        $query = DB::table('view_app_produtos as v')
            ->select(
                'v.codigo_fabrica',
                'v.descricao_produto',
                'v.categoria',
                'v.subcategoria',
                'v.qtd_estoque',
                'v.preco_revenda',
                'v.data_ultima_entrada'
            );

        // 🔹 Filtro por empresa (se a view tiver coluna empresa_id)
        if ($empresa) {
            $query->where('v.empresa_id', $empresa->id);
        }

        // Filtro de texto (código/descrição)
        if ($request->filled('produto')) {
            $produto = $request->get('produto');
            $query->where(function ($q) use ($produto) {
                $q->where('v.codigo_fabrica', 'like', "%{$produto}%")
                    ->orWhere('v.descricao_produto', 'like', "%{$produto}%");
            });
        }

        // Filtro de estoque
        if ($filtroEstoque === 'com') {
            $query->where('v.qtd_estoque', '>', 0);
        } elseif ($filtroEstoque === 'sem') {
            $query->where('v.qtd_estoque', '<', 0);
        } elseif ($filtroEstoque === 'zero') {
            $query->where('v.qtd_estoque', '=', 0);
        }

        // Ordenação
        switch ($sort) {
            case 'codigo':
                $query->orderBy('v.codigo_fabrica', $dir);
                break;
            case 'categoria':
                $query->orderBy('v.categoria', $dir)->orderBy('v.descricao_produto');
                break;
            case 'estoque':
                $query->orderBy('v.qtd_estoque', $dir);
                break;
            case 'preco_revenda':
                $query->orderBy('v.preco_revenda', $dir);
                break;
            case 'ultima_entrada':
                $query->orderBy('v.data_ultima_entrada', $dir);
                break;
            default:
                $query->orderBy('v.descricao_produto', $dir);
        }

        $itens = $query->paginate(20)->appends($request->query());

        return view('estoque.index', [
            'itens'         => $itens,
            'sort'          => $sort,
            'dir'           => $dir,
            'filtroEstoque' => $filtroEstoque,
        ]);
    }
}
