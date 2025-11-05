<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Http\Requests\CategoriaRequest;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $q = Categoria::query();

        if ($request->filled('busca')) {
            $q->where('nome','like','%'.trim($request->busca).'%');
        }
        if ($request->filled('status') && in_array($request->status,['0','1'],true)) {
            $q->where('status',(int)$request->status);
        }

        $pp = in_array((int)$request->por_pagina,[10,25,50,100]) ? (int)$request->por_pagina : 10;

        $categorias = $q->orderBy('nome')->paginate($pp)->withQueryString();

        return view('categorias.index', compact('categorias'));
    }

    public function create() { return view('categorias.create'); }

    public function store(CategoriaRequest $request)
    {
        Categoria::create($request->validated());
        return redirect()->route('categorias.index')->with('success','Categoria cadastrada com sucesso!');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());
        return redirect()->route('categorias.index')->with('success','Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();
        return redirect()->route('categorias.index')->with('success','Categoria excluída com sucesso!');
    }
}
