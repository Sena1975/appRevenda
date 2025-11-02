<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\CampanhaProduto;
use App\Models\Produto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CampanhaProdutoController extends Controller
{
    public function index(Campanha $campanha)
    {
        $restricoes = CampanhaProduto::with(['produto','categoria'])
            ->where('campanha_id', $campanha->id)
            ->orderBy('id','desc')
            ->get();

        $produtos = Produto::orderBy('nome')->get(['id','nome','codfabnumero']);
        $categorias = Categoria::orderBy('nome')->get(['id','nome']);

        return view('campanhas.restricoes', compact('campanha','restricoes','produtos','categorias'));
    }

    public function store(Request $request, Campanha $campanha)
    {
        $data = $request->validate([
            'produto_id' => 'nullable|integer|exists:appproduto,id',
            'codfabnumero' => 'nullable|string|max:30',
            'categoria_id' => 'nullable|integer|exists:appcategoria,id',
            'quantidade_minima' => 'required|integer|min:1',
            'peso_participacao' => 'required|numeric|min:0',
            'observacao' => 'nullable|string',
        ]);

        $data['campanha_id'] = $campanha->id;

        // Pelo menos uma chave de escopo precisa existir: produto OU codfab OU categoria
        if (empty($data['produto_id']) && empty($data['codfabnumero']) && empty($data['categoria_id'])) {
            return back()->withErrors(['scopo' => 'Informe produto, codfab ou categoria.'])->withInput();
        }

        CampanhaProduto::create($data);

        return back()->with('ok', 'Restrição adicionada.');
    }

    public function destroy(Campanha $campanha, $id)
    {
        CampanhaProduto::where('campanha_id', $campanha->id)->where('id', $id)->delete();
        return back()->with('ok', 'Restrição removida.');
    }
}
