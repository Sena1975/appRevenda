<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
 public function index(Request $request)
{
    $query = \App\Models\Produto::with(['categoria', 'subcategoria', 'fornecedor']);

    if ($request->filled('busca')) {
        $query->where(function ($q) use ($request) {
            $q->where('nome', 'like', '%' . $request->busca . '%')
              ->orWhere('codfab', 'like', '%' . $request->busca . '%');
        });
    }

    if ($request->filled('categoria_id')) {
        $query->where('categoria_id', $request->categoria_id);
    }

    $porPagina = $request->get('por_pagina', 10);

    $produtos = $query->orderBy('nome')->paginate($porPagina);
    $categorias = \App\Models\Categoria::orderBy('nome')->get();

    return view('produtos.index', compact('produtos', 'categorias'));
}



    public function create()
    {
        $categorias = Categoria::all();
        $subcategorias = Subcategoria::all();
        $fornecedores = Fornecedor::all();
        return view('produtos.create', compact('categorias', 'subcategorias', 'fornecedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'fornecedor_id' => 'required',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $dados = $request->all();

        if ($request->hasFile('imagem')) {
            $nomeArquivo = time() . '.' . $request->imagem->extension();
            $request->imagem->move(public_path('imagens/produtos'), $nomeArquivo);
            $dados['imagem'] = 'imagens/produtos/' . $nomeArquivo;
        }

        Produto::create($dados);

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    /**
     * Retorna preço e pontuação pelo codfabnumero
     */
    public function getPreco($codfabnumero)
    {
        $hoje = now()->toDateString();

        $preco = DB::table('apptabelapreco')
            ->join('appproduto', 'apptabelapreco.produto_id', '=', 'appproduto.id')
            ->where('appproduto.codfabnumero', $codfabnumero)
            ->where('apptabelapreco.status', 1)
            ->whereDate('apptabelapreco.data_inicio', '<=', $hoje)
            ->whereDate('apptabelapreco.data_fim', '>=', $hoje)
            ->orderByDesc('apptabelapreco.data_inicio')
            ->select('apptabelapreco.preco_revenda', 'apptabelapreco.pontuacao')
            ->first();

        if ($preco) {
            return response()->json([
                'preco_revenda' => (float) $preco->preco_revenda,
                'pontuacao' => (float) $preco->pontuacao,
            ]);
        }

        return response()->json(['preco_revenda' => 0, 'pontuacao' => 0]);
    }

    public function edit(Produto $produto)
    {
        $categorias = Categoria::all();
        $subcategorias = Subcategoria::all();
        $fornecedores = Fornecedor::all();
        return view('produtos.edit', compact('produto', 'categorias', 'subcategorias', 'fornecedores'));
    }

    public function update(Request $request, Produto $produto)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'fornecedor_id' => 'required',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $dados = $request->all();

        if ($request->hasFile('imagem')) {
            if ($produto->imagem && file_exists(public_path($produto->imagem))) {
                unlink(public_path($produto->imagem));
            }

            $nomeArquivo = time() . '.' . $request->imagem->extension();
            $request->imagem->move(public_path('imagens/produtos'), $nomeArquivo);
            $dados['imagem'] = 'imagens/produtos/' . $nomeArquivo;
        }

        $produto->update($dados);

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();
        return redirect()->route('produtos.index')->with('success', 'Produto excluído com sucesso!');
    }
}
