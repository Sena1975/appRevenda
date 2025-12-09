<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormaPagamentoRequest;
use App\Models\FormaPagamento;
use Illuminate\Http\Request;

class FormaPagamentoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;

        // Base já filtrando pela empresa
        $q = FormaPagamento::query()
            ->when($empresaId, fn ($qq) => $qq->where('empresa_id', $empresaId));

        if ($request->filled('busca')) {
            $q->where('nome', 'like', '%' . trim($request->busca) . '%');
        }

        if ($request->filled('ativo') && in_array($request->ativo, ['0', '1'], true)) {
            $q->where('ativo', (int) $request->ativo);
        }

        if ($request->filled('gera_receber') && in_array($request->gera_receber, ['0', '1'], true)) {
            $q->where('gera_receber', (int) $request->gera_receber);
        }

        $pp = in_array((int) $request->por_pagina, [10, 25, 50, 100])
            ? (int) $request->por_pagina
            : 10;

        $formas = $q->orderBy('nome')
            ->paginate($pp)
            ->withQueryString();

        return view('formapagamento.index', compact('formas'));
    }

    public function create()
    {
        return view('formapagamento.create');
    }

    public function store(FormaPagamentoRequest $request)
    {
        $user  = $request->user();
        $dados = $request->validated();

        // garante vinculo com a empresa do usuário
        $dados['empresa_id'] = $user->empresa_id ?? null;

        FormaPagamento::create($dados);

        return redirect()
            ->route('formapagamento.index')
            ->with('success', 'Forma cadastrada com sucesso!');
    }

    public function edit(FormaPagamento $formapagamento, Request $request)
    {
        // segurança: a forma deve ser da mesma empresa do usuário
        $user = $request->user();
        if ($user && $formapagamento->empresa_id !== $user->empresa_id) {
            abort(403, 'Esta forma de pagamento não pertence à sua empresa.');
        }

        return view('formapagamento.edit', compact('formapagamento'));
    }

    public function update(FormaPagamentoRequest $request, FormaPagamento $formapagamento)
    {
        $user = $request->user();
        if ($user && $formapagamento->empresa_id !== $user->empresa_id) {
            abort(403, 'Esta forma de pagamento não pertence à sua empresa.');
        }

        $dados = $request->validated();

        // blindagem: nunca muda empresa_id via tela
        unset($dados['empresa_id']);

        $formapagamento->update($dados);

        return redirect()
            ->route('formapagamento.index')
            ->with('success', 'Forma atualizada com sucesso!');
    }

    

    public function destroy(FormaPagamento $formapagamento, Request $request)
    {
        $user = $request->user();
        if ($user && $formapagamento->empresa_id !== $user->empresa_id) {
            abort(403, 'Esta forma de pagamento não pertence à sua empresa.');
        }

        $formapagamento->delete();

        return redirect()
            ->route('formapagamento.index')
            ->with('success', 'Forma excluída com sucesso!');
    }
}
