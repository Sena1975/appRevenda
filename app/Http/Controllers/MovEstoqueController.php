<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovEstoque;
use App\Models\Produto;
use App\Services\EstoqueService;

class MovEstoqueController extends Controller
{
    private EstoqueService $estoque;

    public function __construct(EstoqueService $estoque)
    {
        $this->estoque = $estoque;
    }

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
        $produtos = Produto::orderBy('nome')->get(['id','nome','codfabnumero']);
        return view('movestoque.create', compact('produtos'));
    }

    /**
     * Grava uma movimentação manual (ajuste, entrada, saída)
     */
    public function store(Request $request)
    {
        $request->validate([
            'produto_id'    => 'required|integer|exists:appproduto,id',
            'tipo_mov'      => 'required|string|in:ENTRADA,SAIDA,AJUSTE',
            'quantidade'    => 'required|numeric|min:0.01',
            'preco_unitario'=> 'nullable|numeric|min:0',
            'observacao'    => 'nullable|string|max:255',
        ],[
            'produto_id.required' => 'Selecione um produto.',
            'tipo_mov.in'         => 'Tipo inválido. Use ENTRADA, SAIDA ou AJUSTE.',
        ]);

        // Atualiza saldo + registra a movimentação (via service)
        $this->estoque->registrarMovimentoManual(
            (int)$request->produto_id,
            $request->tipo_mov,
            (float)$request->quantidade,
            (float)($request->preco_unitario ?? 0),
            (string)($request->observacao ?? '')
        );

        // Observação: também mantemos um registro pelo Model caso você liste por Eloquent:
        MovEstoque::create([
            'produto_id'     => $request->produto_id,
            'tipo_mov'       => $request->tipo_mov === 'AJUSTE'
                                  ? ((float)$request->quantidade >= 0 ? 'ENTRADA' : 'SAIDA')
                                  : $request->tipo_mov,
            'quantidade'     => $request->tipo_mov === 'SAIDA' ? -abs($request->quantidade) : abs($request->quantidade),
            'preco_unitario' => $request->preco_unitario ?? 0,
            'observacao'     => $request->observacao ?? '',
            'status'         => 'CONFIRMADO',
            'data_mov'       => now(),
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
