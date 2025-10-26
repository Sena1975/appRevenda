<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;

class AppFornecedorController extends Controller
{
    public function index()
    {
        // Busca todos os fornecedores, ordenados por nome
        $fornecedores = Fornecedor::orderBy('nomefantasia', 'asc')->get();

        // Envia os dados para a view
        return view('fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('fornecedores.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['status'] = $request->has('status');

        Fornecedor::create($data);

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $fornecedor = Fornecedor::findOrFail($id);
        return view('fornecedores.edit', compact('fornecedor'));
    }

    public function update(Request $request, $id)
    {
        $fornecedor = Fornecedor::findOrFail($id);
        $data = $request->all();
        $data['status'] = $request->has('status');
        $fornecedor->update($data);

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy($id)
    {
        Fornecedor::destroy($id);
        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor excluído com sucesso!');
    }
}
