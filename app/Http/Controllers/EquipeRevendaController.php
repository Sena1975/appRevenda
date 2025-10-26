<?php

namespace App\Http\Controllers;

use App\Models\EquipeRevenda;
use App\Models\Revendedora;
use Illuminate\Http\Request;

class EquipeRevendaController extends Controller
{
    public function index()
    {
        $equipes = \App\Models\EquipeRevenda::with('revendedora')->orderBy('nome')->paginate(10);
        return view('equiperevenda.index', compact('equipes'));
    }

    public function create()
    {
        $revendedoras = \App\Models\Revendedora::orderBy('nome')->get();
        return view('equiperevenda.create', compact('revendedoras'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'revendedora_id' => 'nullable|exists:apprevendedora,id',
        ]);

        \App\Models\EquipeRevenda::create($request->all());

        return redirect()->route('equiperevenda.index')->with('success', 'Equipe cadastrada com sucesso!');
    }

    public function edit($id)
    {
        $equipe = \App\Models\EquipeRevenda::findOrFail($id);
        $revendedoras = \App\Models\Revendedora::orderBy('nome')->get();
        return view('equiperevenda.edit', compact('equipe', 'revendedoras'));
    }

    public function update(Request $request, $id)
    {
        $equipe = \App\Models\EquipeRevenda::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'revendedora_id' => 'nullable|exists:apprevendedora,id',
        ]);

        $equipe->update($request->all());

        return redirect()->route('equiperevenda.index')->with('success', 'Equipe atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $equipe = EquipeRevenda::findOrFail($id);
        $equipe->delete();

        return redirect()->route('equiperevenda.index')->with('success', 'Equipe excluída com sucesso!');
    }
}
