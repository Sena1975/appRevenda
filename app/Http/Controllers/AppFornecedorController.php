<?php

namespace App\Http\Controllers;

use App\Http\Requests\FornecedorRequest;
use Illuminate\Http\Request;
use App\Models\Fornecedor;

class AppFornecedorController extends Controller
{
    public function index(Request $request)
    {
        $q = Fornecedor::query();

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $q->where(function($w) use ($busca) {
                $w->where('razaosocial', 'like', "%{$busca}%")
                  ->orWhere('nomefantasia', 'like', "%{$busca}%")
                  ->orWhere('cnpj', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0','1'], true)) {
            $q->where('status', (int)$request->status);
        }

        $allowed = [10,25,50,100];
        $porPagina = (int)$request->get('por_pagina', 10);
        if (!in_array($porPagina, $allowed, true)) {
            $porPagina = 10;
        }

        $fornecedores = $q->orderBy('razaosocial')
                          ->paginate($porPagina)
                          ->withQueryString();

        return view('fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('fornecedores.create');
    }

    public function store(FornecedorRequest $request)
    {
        Fornecedor::create($request->validated());

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit(Fornecedor $fornecedore)
    {
        return view('fornecedores.edit', ['fornecedor' => $fornecedore]);
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedore)
    {
        $fornecedore->update($request->validated());

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $fornecedore)
    {
        $fornecedore->delete();

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor excluído com sucesso!');
    }

    public function show(Fornecedor $fornecedore)
    {
        return view('fornecedores.show', ['fornecedor' => $fornecedore]);
    }
}
