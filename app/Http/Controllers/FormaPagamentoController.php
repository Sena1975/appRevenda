<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormaPagamentoRequest;
use App\Models\FormaPagamento;
use Illuminate\Http\Request;

class FormaPagamentoController extends Controller
{
    public function index(Request $request)
    {
        $q = FormaPagamento::query();

        if ($request->filled('busca')) {
            $q->where('nome','like','%'.trim($request->busca).'%');
        }
        if ($request->filled('ativo') && in_array($request->ativo,['0','1'],true)) {
            $q->where('ativo',(int)$request->ativo);
        }
        if ($request->filled('gera_receber') && in_array($request->gera_receber,['0','1'],true)) {
            $q->where('gera_receber',(int)$request->gera_receber);
        }

        $pp = in_array((int)$request->por_pagina,[10,25,50,100]) ? (int)$request->por_pagina : 10;

        $formas = $q->orderBy('nome')
                    ->paginate($pp)
                    ->withQueryString();

        return view('formapagamento.index', compact('formas'));
    }

    public function create() { return view('formapagamento.create'); }

    public function store(FormaPagamentoRequest $request)
    {
        FormaPagamento::create($request->validated());
        return redirect()->route('formapagamento.index')->with('success','Forma cadastrada com sucesso!');
    }

    public function edit(FormaPagamento $formapagamento)
    {
        return view('formapagamento.edit', compact('formapagamento'));
    }

    public function update(FormaPagamentoRequest $request, FormaPagamento $formapagamento)
    {
        $formapagamento->update($request->validated());
        return redirect()->route('formapagamento.index')->with('success','Forma atualizada com sucesso!');
    }

    public function destroy(FormaPagamento $formapagamento)
    {
        $formapagamento->delete();
        return redirect()->route('formapagamento.index')->with('success','Forma excluída com sucesso!');
    }
}
