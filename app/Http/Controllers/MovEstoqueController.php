<?php

namespace App\Http\Controllers;

use App\Models\MovEstoque;
use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Http\Request;

class MovEstoqueController extends Controller
{
    /**
     * Lista todas as movimentações
     */
    public function index()
    {
        $movs = MovEstoque::with('produto')->orderBy('data_mov', 'desc')->paginate(20);
        return view('movestoque.index', compact('movs'));
    }

    /**
     * Mostra o formulário para registrar nova movimentação
     */
    public function create()
    {
        $produtos = Produto::orderBy('nome')->get();
        return view('movestoque.create', compact('produtos'));
    }

    /**
     * Grava uma movimentação manual (ajuste, avaria, entrada, saída)
     */
    public function store(Request $request)
    {
        $request->validate([
            'produto_id' => 'required',
            'tipo_mov' => 'required',
            'quantidade' => 'required|numeric|min:0.01',
        ]);

        MovEstoque::create([
            'produto_id' => $request->produto_id,
            'tipo_mov' => $request->tipo_mov,
            'quantidade' => $request->quantidade,
            'preco_unitario' => $request->preco_unitario ?? 0,
            'observacao' => $request->observacao ?? '',
            'status' => 'CONFIRMADO',
            'data_mov' => now(),
        ]);

        return redirect()->route('movestoque.index')->with('success', 'Movimentação registrada com sucesso!');
    }

    /**
     * Mostra detalhes de uma movimentação
     */
    public function show($id)
    {
        $mov = MovEstoque::with('produto')->findOrFail($id);
        return view('movestoque.show', compact('mov'));
    }
}
