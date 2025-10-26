<?php

namespace App\Http\Controllers;

use App\Models\Subcategoria;
use App\Models\Categoria;
use Illuminate\Http\Request;

class SubcategoriaController extends Controller
{
    public function index()
    {
        $subcategorias = Subcategoria::with('categoria')->get();
        return view('subcategorias.index', compact('subcategorias'));
    }

    public function create()
    {
        $categorias = Categoria::all();
        return view('subcategorias.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        Subcategoria::create($request->all());
        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria cadastrada com sucesso!');
    }

    public function edit($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $categorias = Categoria::all();
        return view('subcategorias.edit', compact('subcategoria', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $subcategoria->update($request->all());
        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $subcategoria->delete();
        return redirect()->route('subcategorias.index')->with('success', 'Subcategoria excluída com sucesso!');
    }
}
