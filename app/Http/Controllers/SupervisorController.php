<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Http\Requests\SupervisorRequest;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        $q = Supervisor::query();

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $q->where(function($w) use ($busca){
                $w->where('nome','like',"%{$busca}%")
                  ->orWhere('cpf','like',"%{$busca}%")
                  ->orWhere('email','like',"%{$busca}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0','1'], true)) {
            $q->where('status', (int)$request->status);
        }

        $pp = in_array((int)$request->por_pagina,[10,25,50,100]) ? (int)$request->por_pagina : 10;

        $supervisores = $q->orderBy('nome')->paginate($pp)->withQueryString();

        return view('supervisores.index', compact('supervisores'));
    }

    public function create() { return view('supervisores.create'); }

    public function store(SupervisorRequest $request)
    {
        Supervisor::create($request->validated());
        return redirect()->route('supervisores.index')->with('success','Supervisor cadastrado com sucesso!');
    }

    public function edit(Supervisor $supervisore)
    {
        return view('supervisores.edit', ['supervisor'=>$supervisore]);
    }

    public function update(SupervisorRequest $request, Supervisor $supervisore)
    {
        $supervisore->update($request->validated());
        return redirect()->route('supervisores.index')->with('success','Supervisor atualizado com sucesso!');
    }

    public function destroy(Supervisor $supervisore)
    {
        $supervisore->delete();
        return redirect()->route('supervisores.index')->with('success','Supervisor excluído com sucesso!');
    }

    public function show(Supervisor $supervisore)
    {
        return view('supervisores.show', ['supervisor'=>$supervisore]);
    }
}
