<?php

namespace App\Http\Controllers;

use App\Models\Subcategoria;
use App\Models\Categoria;
use App\Http\Requests\SubcategoriaRequest;
use Illuminate\Http\Request;

class SubcategoriaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $q = Subcategoria::with('categoria')
            ->where('empresa_id', $empresaId);

        if ($request->filled('busca')) {
            $q->where('nome', 'like', '%' . trim($request->busca) . '%');
        }
        if ($request->filled('categoria_id')) {
            $q->where('categoria_id', (int) $request->categoria_id);
        }
        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $q->where('status', (int)$request->status);
        }

        $pp = in_array((int)$request->por_pagina, [10, 25, 50, 100]) ? (int)$request->por_pagina : 10;

        $subcategorias = $q->orderBy('nome')->paginate($pp)->withQueryString();

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('subcategorias.index', compact('subcategorias', 'categorias'));
    }

    public function create(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('subcategorias.create', compact('categorias'));
    }

    public function store(SubcategoriaRequest $request)
    {
        $data = $request->validated();
        $data['empresa_id'] = $request->user()->empresa_id;

        Subcategoria::create($data);

        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria cadastrada com sucesso!');
    }

    public function edit(Request $request, Subcategoria $subcategoria)
    {
        if ($subcategoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $empresaId = $request->user()->empresa_id;

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('subcategorias.edit', compact('subcategoria', 'categorias'));
    }

    public function update(SubcategoriaRequest $request, Subcategoria $subcategoria)
    {
        if ($subcategoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $data = $request->validated();
        unset($data['empresa_id']); // segurança

        $subcategoria->update($data);

        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria atualizada com sucesso!');
    }

    public function destroy(Request $request, Subcategoria $subcategoria)
    {
        if ($subcategoria->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $subcategoria->delete();

        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria excluída com sucesso!');
    }
}
