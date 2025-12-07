<?php

namespace App\Http\Controllers;

use App\Http\Requests\FornecedorRequest;
use Illuminate\Http\Request;
use App\Models\Fornecedor;

class AppFornecedorController extends Controller
{
    public function index(Request $request)
    {
        // Sempre começa pelos fornecedores da empresa do usuário
        $q = Fornecedor::daEmpresa(); // usa o scope do model

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $q->where(function ($w) use ($busca) {
                $w->where('razaosocial', 'like', "%{$busca}%")
                    ->orWhere('nomefantasia', 'like', "%{$busca}%")
                    ->orWhere('cnpj', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $q->where('status', (int) $request->status);
        }

        $allowed   = [10, 25, 50, 100];
        $porPagina = (int) $request->get('por_pagina', 10);
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
        $usuario   = $request->user();
        $empresaId = $usuario?->empresa_id;

        $dados = $request->validated();
        $dados['empresa_id'] = $empresaId; // fornecedor pertence à empresa do usuário

        Fornecedor::create($dados);

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit(Request $request, Fornecedor $fornecedore)
    {
        $this->autorizarEmpresa($request, $fornecedore);

        return view('fornecedores.edit', ['fornecedor' => $fornecedore]);
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedore)
    {
        $this->autorizarEmpresa($request, $fornecedore);

        $dados = $request->validated();

        // Garante que ninguém troque a empresa pelo formulário
        unset($dados['empresa_id']);

        $fornecedore->update($dados);

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Request $request, Fornecedor $fornecedore)
    {
        $this->autorizarEmpresa($request, $fornecedore);

        $fornecedore->delete();

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor excluído com sucesso!');
    }

    public function show(Request $request, Fornecedor $fornecedore)
    {
        $this->autorizarEmpresa($request, $fornecedore);

        return view('fornecedores.show', ['fornecedor' => $fornecedore]);
    }

    /**
     * Garante que o fornecedor pertence à mesma empresa do usuário logado.
     */
    protected function autorizarEmpresa(Request $request, Fornecedor $fornecedor): void
    {
        $usuario = $request->user();

        if (!$usuario || $usuario->empresa_id !== $fornecedor->empresa_id) {
            abort(403, 'Fornecedor não pertence à sua empresa.');
        }
    }
}
