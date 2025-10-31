<?php

namespace App\Http\Controllers;

use App\Models\FormaPagamento;
use Illuminate\Http\Request;

class FormaPagamentoController extends Controller
{
public function index()
{
    $formas = FormaPagamento::orderBy('nome')->paginate(10);
    return view('formapagamento.index', compact('formas'));
}


    public function create()
    {
        return view('formapagamento.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:80',
            'gera_receber' => 'required|boolean',
            'max_parcelas' => 'required|integer|min:1',
        ]);

        FormaPagamento::create($request->all());

        return redirect()->route('formapagamento.index')->with('success', 'Forma de pagamento cadastrada com sucesso!');
    }
}
