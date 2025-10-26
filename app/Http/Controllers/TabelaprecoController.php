<?php

namespace App\Http\Controllers;

use App\Models\Tabelapreco;
use App\Models\Produto;
use Illuminate\Http\Request;

class TabelaprecoController extends Controller
{
    public function index()
    {
        $tabelas = Tabelapreco::with('produto')->orderBy('id', 'desc')->get();
        return view('tabelapreco.index', compact('tabelas'));
    }

    public function create()
    {
        $produtos = Produto::orderBy('nome')->get();
        return view('tabelapreco.create', compact('produtos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produto_id' => 'required',
            'preco_revenda' => 'required|numeric',
        ]);

        Tabelapreco::create($request->all());
        return redirect()->route('tabelapreco.index')->with('success', 'Tabela de preço cadastrada com sucesso!');
    }

    public function edit($id)
    {
        $tabela = Tabelapreco::findOrFail($id);
        $produtos = Produto::orderBy('nome')->get();
        return view('tabelapreco.edit', compact('tabela', 'produtos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'produto_id' => 'required',
            'preco_revenda' => 'required|numeric',
        ]);

        $tabela = Tabelapreco::findOrFail($id);
        $tabela->update($request->all());

        return redirect()->route('tabelapreco.index')->with('success', 'Tabela de preço atualizada com sucesso!');
    }

    public function destroy($id)
    {
        Tabelapreco::destroy($id);
        return redirect()->route('tabelapreco.index')->with('success', 'Registro excluído com sucesso!');
    }
}
