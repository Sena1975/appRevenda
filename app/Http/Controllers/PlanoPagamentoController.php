<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanoPagamento;
use App\Models\FormaPagamento;

class PlanoPagamentoController extends Controller
{
    public function index()
    {
        $planos = PlanoPagamento::with('formaPagamento')->orderBy('id', 'desc')->paginate(10);
        return view('planopagamento.index', compact('planos'));
    }

    public function create()
    {
        $formas = FormaPagamento::orderBy('nome')->get();
        return view('planopagamento.create', compact('formas'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'codplano'          => 'required|string|max:10|unique:appplanopagamento,codplano',
            'descricao'         => 'required|string|max:100',
            'formapagamento_id' => 'required|integer|exists:appformapagamento,id',
            'parcelas'          => 'required|integer|min:1',
            'prazo1'            => 'nullable|integer|min:0',
            'prazo2'            => 'nullable|integer|min:0',
            'prazo3'            => 'nullable|integer|min:0',
            'prazomedio'        => 'nullable|integer|min:0',
            'ativo'             => 'nullable|boolean',
        ]);

        PlanoPagamento::create($dados);

        return redirect()->route('planopagamento.index')->with('success', 'Plano de pagamento cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $plano = PlanoPagamento::findOrFail($id);
        $formas = FormaPagamento::orderBy('nome')->get();
        return view('planopagamento.edit', compact('plano', 'formas'));
    }

    public function update(Request $request, $id)
    {
        $plano = PlanoPagamento::findOrFail($id);

        $dados = $request->validate([
            'descricao'         => 'required|string|max:100',
            'formapagamento_id' => 'required|integer|exists:appformapagamento,id',
            'parcelas'          => 'required|integer|min:1',
            'prazo1'            => 'nullable|integer|min:0',
            'prazo2'            => 'nullable|integer|min:0',
            'prazo3'            => 'nullable|integer|min:0',
            'prazomedio'        => 'nullable|integer|min:0',
            'ativo'             => 'nullable|boolean',
        ]);

        $plano->update($dados);

        return redirect()->route('planopagamento.index')->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroy($id)
    {
        PlanoPagamento::findOrFail($id)->delete();
        return redirect()->route('planopagamento.index')->with('success', 'Plano excluído com sucesso!');
    }
    public function getByForma($forma_id)
{
    $planos = \App\Models\PlanoPagamento::where('formapagamento_id', $forma_id)
        ->where('ativo', 1)
        ->orderBy('descricao')
        ->get(['id', 'codplano', 'descricao', 'parcelas', 'prazomedio']);

    return response()->json($planos);
}

}
