<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    /**
     * Lista o estoque consolidado
     */
    public function index(Request $request)
    {
        $query = Estoque::with('produto')->orderBy('id', 'desc');

        // Filtro simples por nome do produto ou código de fábrica
        if ($busca = $request->get('busca')) {
            $query->whereHas('produto', function ($q) use ($busca) {
                $q->where('nome', 'like', '%' . $busca . '%')
                  ->orWhere('codfabnumero', $busca)
                  ->orWhere('codfab', $busca)
                  ->orWhere('codfabtexto', 'like', '%' . $busca . '%');
            });
        }

        $estoques = $query->paginate(20)->withQueryString();

        return view('estoque.index', compact('estoques'));
    }

    /**
     * Tela para ajustar um item de estoque
     */
    public function edit($id)
    {
        $estoque = Estoque::with('produto')->findOrFail($id);

        return view('estoque.edit', compact('estoque'));
    }

    /**
     * Atualiza os dados de estoque
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'estoque_gerencial' => ['required', 'numeric'],
            'reservado'         => ['required', 'numeric'],
            'avaria'            => ['required', 'numeric'],
        ]);

        $estoque = Estoque::findOrFail($id);

        $estoque->update([
            'estoque_gerencial' => $request->estoque_gerencial,
            'reservado'         => $request->reservado,
            'avaria'            => $request->avaria,
        ]);

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Estoque atualizado com sucesso!');
    }
}
