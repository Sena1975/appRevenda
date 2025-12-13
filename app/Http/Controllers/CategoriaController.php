<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Http\Requests\CategoriaRequest;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $q = Categoria::query()->where('empresa_id', $empresaId);

        if ($request->filled('busca')) {
            $q->where('nome', 'like', '%' . trim($request->busca) . '%');
        }
        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $q->where('status', (int)$request->status);
        }

        $pp = in_array((int)$request->por_pagina, [10, 25, 50, 100]) ? (int)$request->por_pagina : 10;

        $categorias = $q->orderBy('nome')->paginate($pp)->withQueryString();

        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(CategoriaRequest $request)
    {
        $data = $request->validated();
        $data['empresa_id'] = $request->user()->empresa_id;

        Categoria::create($data);

        return redirect()->route('categorias.index')->with('success', 'Categoria cadastrada com sucesso!');
    }

    public function edit(Request $request, Categoria $categoria)
    {
        if ($categoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        return view('categorias.edit', compact('categoria'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria)
    {
        if ($categoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $data = $request->validated();
        unset($data['empresa_id']);

        $categoria->update($data);

        return redirect()->route('categorias.index')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        if ($categoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('success', 'Categoria excluída com sucesso!');
    }
}
