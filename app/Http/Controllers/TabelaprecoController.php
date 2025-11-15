<?php

namespace App\Http\Controllers;

use App\Http\Requests\TabelaPrecoRequest;
use App\Models\TabelaPreco;
use App\Models\Produto;
use Illuminate\Http\Request;

class TabelaprecoController extends Controller
{
    /**
     * Listagem com filtros e paginação
     * Filtros:
     * - busca: por nome do produto ou codfab/codfabnumero
     * - produto_id
     * - status (1 ativo, 0 inativo)
     * - vigencia: atual|futura|expirada
     */
    public function index(Request $request)
    {
        $query = TabelaPreco::query()
            ->with(['produto' => function($q) {
                $q->select('id','nome','codfab','codfabnumero');
            }]);

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $query->where(function($q) use ($busca) {
                $q->where('codfab', 'like', "%{$busca}%")
                  ->orWhereHas('produto', function($qp) use ($busca) {
                      $qp->where('nome', 'like', "%{$busca}%")
                         ->orWhere('codfab', 'like', "%{$busca}%")
                         ->orWhere('codfabnumero', 'like', "%{$busca}%");
                  });
            });
        }

        if ($request->filled('produto_id')) {
            $query->where('produto_id', $request->produto_id);
        }

        if ($request->filled('status')) {
            $status = (int) $request->status;
            if (in_array($status, [0,1], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('vigencia')) {
            $hoje = now()->toDateString();
            switch ($request->vigencia) {
                case 'atual':
                    $query->where('data_inicio', '<=', $hoje)
                          ->where('data_fim', '>=', $hoje);
                    break;
                case 'futura':
                    $query->where('data_inicio', '>', $hoje);
                    break;
                case 'expirada':
                    $query->where('data_fim', '<', $hoje);
                    break;
            }
        }

        $allowed = [10,25,50,100];
        $porPagina = (int) $request->get('por_pagina', 10);
        if (! in_array($porPagina, $allowed)) {
            $porPagina = 10;
        }

        $tabelas = $query
            ->orderByRaw('data_inicio desc, id desc')
            ->paginate($porPagina)
            ->withQueryString();

        // Para o filtro por produto em select
        $produtos = Produto::select('id','nome','codfab','codfabnumero')
            ->orderBy('nome')->limit(500)->get();

        return view('tabelapreco.index', compact('tabelas', 'produtos'));
    }

public function create()
{
    // 🔹 Sem filtros escondidos (status, relacionamentos, etc.)
    // 🔹 Ordena por nome, mas coloca quem tem CODFAB primeiro
    // 🔹 Só seleciona os campos usados na view
    $produtos = \App\Models\Produto::query()
        ->select('id', 'nome', 'codfabnumero')   
        ->orderByRaw("CASE WHEN (codfabnumero IS NULL OR codfabnumero='') THEN 1 ELSE 0 END, nome ASC")
        ->get();

    return view('tabelapreco.create', compact('produtos'));
}


    public function store(TabelaPrecoRequest $request)
    {
        TabelaPreco::create($request->validated());

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço criada com sucesso!');
    }

    public function show(TabelaPreco $tabelapreco)
    {
        $tabelapreco->load(['produto' => function($q) {
            $q->select('id','nome','codfab','codfabnumero');
        }]);

        return view('tabelapreco.show', compact('tabelapreco'));
    }

    public function edit(TabelaPreco $tabelapreco)
    {
        $produtos = Produto::select('id','nome','codfab','codfabnumero')
            ->orderBy('nome')->limit(500)->get();

        return view('tabelapreco.edit', compact('tabelapreco','produtos'));
    }

    public function update(TabelaPrecoRequest $request, TabelaPreco $tabelapreco)
    {
        $tabelapreco->update($request->validated());

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço atualizada com sucesso!');
    }

    public function destroy(TabelaPreco $tabelapreco)
    {
        $tabelapreco->delete();

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço excluída com sucesso!');
    }
}
