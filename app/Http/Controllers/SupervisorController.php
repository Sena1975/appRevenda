<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Http\Requests\SupervisorRequest;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    protected function autorizarEmpresa(Request $request, Supervisor $supervisor): void
    {
        $user = $request->user();

        if (!$user || $user->empresa_id !== $supervisor->empresa_id) {
            abort(403, 'Supervisor não pertence à sua empresa.');
        }
    }

    public function index(Request $request)
    {
        // sempre começa pela empresa do usuário
        $q = Supervisor::daEmpresa();

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $q->where(function ($w) use ($busca) {
                $w->where('nome', 'like', "%{$busca}%")
                  ->orWhere('cpf', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0','1'], true)) {
            $q->where('status', (int) $request->status);
        }

        $pp = in_array((int) $request->por_pagina, [10,25,50,100]) ? (int) $request->por_pagina : 10;

        $supervisores = $q->orderBy('nome')
                          ->paginate($pp)
                          ->withQueryString();

        return view('supervisores.index', compact('supervisores'));
    }

    public function create()
    {
        return view('supervisores.create');
    }

    public function store(SupervisorRequest $request)
    {
        $user  = $request->user();
        $dados = $request->validated();

        $dados['empresa_id'] = $user->empresa_id;

        Supervisor::create($dados);

        return redirect()
            ->route('supervisores.index')
            ->with('success', 'Supervisor cadastrado com sucesso!');
    }

    public function edit(Request $request, Supervisor $supervisore)
    {
        $this->autorizarEmpresa($request, $supervisore);

        return view('supervisores.edit', ['supervisor' => $supervisore]);
    }

    public function update(SupervisorRequest $request, Supervisor $supervisore)
    {
        $this->autorizarEmpresa($request, $supervisore);

        $dados = $request->validated();
        unset($dados['empresa_id']); // não deixa trocar empresa

        $supervisore->update($dados);

        return redirect()
            ->route('supervisores.index')
            ->with('success', 'Supervisor atualizado com sucesso!');
    }

    public function destroy(Request $request, Supervisor $supervisore)
    {
        $this->autorizarEmpresa($request, $supervisore);

        $supervisore->delete();

        return redirect()
            ->route('supervisores.index')
            ->with('success', 'Supervisor excluído com sucesso!');
    }

    public function show(Request $request, Supervisor $supervisore)
    {
        $this->autorizarEmpresa($request, $supervisore);

        return view('supervisores.show', ['supervisor' => $supervisore]);
    }
}
