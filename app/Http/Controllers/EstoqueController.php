<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    /**
     * Exibe o estoque consolidado
     */
    public function index()
    {
        $estoques = Estoque::with('produto')->orderBy('id', 'desc')->paginate(20);
        return view('estoque.index', compact('estoques'));
    }

    /**
     * Mostra detalhes de um produto específico
     */
    public function show($id)
    {
        $estoque = Estoque::with('produto')->findOrFail($id);
        return view('estoque.show', compact('estoque'));
    }

    /**
     * Exibe tela para ajuste manual de estoque
     */
    public function edit($id)
    {
        $estoque = Estoque::findOrFail($id);
        return view('estoque.edit', compact('estoque'));
    }

    /**
     * Atualiza estoque manualmente (ex: ajuste)
     */
    public function update(Request $request, $id)
    {
        $estoque = Estoque::findOrFail($id);

        $estoque->update([
            'estoque_gerencial' => $request->estoque_gerencial,
            'reservado' => $request->reservado,
            'avaria' => $request->avaria,
        ]);

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Estoque atualizado com sucesso!');
    }
}
