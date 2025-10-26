<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function index()
    {
        $supervisores = Supervisor::orderBy('nome')->get();
        return view('supervisores.index', compact('supervisores'));
    }

    public function create()
    {
        return view('supervisores.create');
    }

    public function show($id)
    {
        $supervisor = Supervisor::with('equipes')->findOrFail($id);
        return view('supervisores.show', compact('supervisor'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'cpf' => 'nullable|string|max:14',
            'email' => 'nullable|email|max:120',
        ]);

        Supervisor::create($request->all());

        return redirect()->route('supervisores.index')->with('success', 'Supervisor cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $supervisor = Supervisor::findOrFail($id);
        return view('supervisores.edit', compact('supervisor'));
    }


    public function update(Request $request, $id)
    {
        $supervisor = Supervisor::findOrFail($id);
        $supervisor->update($request->all());

        return redirect()->route('supervisores.index')->with('success', 'Supervisor atualizado com sucesso!');
    }

    public function destroy(Supervisor $supervisor)
    {
        $supervisor->delete();
        return redirect()->route('supervisores.index')->with('success', 'Supervisor excluído com sucesso!');
    }
}
