<?php

namespace App\Http\Controllers;

use App\Models\Revendedora;
use Illuminate\Http\Request;

class RevendedoraController extends Controller
{
public function index()
{
    $revendedoras = \App\Models\Revendedora::orderBy('nome')->paginate(10);
    return view('revendedoras.index', compact('revendedoras'));
}

    public function create()
    {
        return view('revendedoras.create');
    }

public function store(Request $request)
{
    $request->validate([
        'nome' => 'required|string|max:255',
        'cpf' => 'required|string|max:14|unique:apprevendedora,cpf',
    ]);

    \App\Models\Revendedora::create($request->all());

    return redirect()->route('revendedoras.index')->with('success', 'Revendedora cadastrada com sucesso!');
}


    public function edit($id)
    {
        $revendedora = Revendedora::findOrFail($id);
        return view('revendedoras.edit', compact('revendedora'));
    }

    public function update(Request $request, $id)
    {
        $revendedora = Revendedora::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:apprevendedora,cpf,' . $revendedora->id,
        ]);

        $revendedora->update($request->all());

        return redirect()->route('revendedoras.index')->with('success', 'Dados atualizados com sucesso!');
    }

    public function destroy($id)
    {
        $revendedora = Revendedora::findOrFail($id);
        $revendedora->delete();

        return redirect()->route('revendedoras.index')->with('success', 'Revendedora excluída com sucesso!');
    }
}
