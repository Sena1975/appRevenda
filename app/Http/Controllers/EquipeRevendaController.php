<?php

namespace App\Http\Controllers;

use App\Models\EquipeRevenda;
use App\Models\Revendedora;
use Illuminate\Http\Request;

class EquipeRevendaController extends Controller
{
    protected function autorizarEmpresa(Request $request, EquipeRevenda $equipe): void
    {
        $user = $request->user();

        if (!$user || $user->empresa_id !== $equipe->empresa_id) {
            abort(403, 'Equipe não pertence à sua empresa.');
        }
    }

    public function index(Request $request)
    {
        $equipes = EquipeRevenda::daEmpresa()
            ->with('revendedora')
            ->orderBy('nome')
            ->paginate(10);

        return view('equiperevenda.index', compact('equipes'));
    }

    public function create(Request $request)
    {
        // Só revendedoras da empresa do usuário
        $revendedoras = Revendedora::daEmpresa()
            ->orderBy('nome')
            ->get();

        return view('equiperevenda.create', compact('revendedoras'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $dados = $request->validate([
            'nome'           => 'required|string|max:255',
            'descricao'      => 'nullable|string|max:1000',
            'revendedora_id' => 'nullable|exists:apprevendedora,id',
            'supervisor_id'  => 'nullable|integer',
            'status'         => 'nullable|string|max:20',
        ]);

        $dados['empresa_id'] = $user->empresa_id;

        EquipeRevenda::create($dados);

        return redirect()
            ->route('equiperevenda.index')
            ->with('success', 'Equipe cadastrada com sucesso!');
    }

    public function edit(Request $request, $id)
    {
        $equipe = EquipeRevenda::findOrFail($id);
        $this->autorizarEmpresa($request, $equipe);

        $revendedoras = Revendedora::daEmpresa()
            ->orderBy('nome')
            ->get();

        return view('equiperevenda.edit', compact('equipe', 'revendedoras'));
    }

    public function update(Request $request, $id)
    {
        $equipe = EquipeRevenda::findOrFail($id);
        $this->autorizarEmpresa($request, $equipe);

        $dados = $request->validate([
            'nome'           => 'required|string|max:255',
            'descricao'      => 'nullable|string|max:1000',
            'revendedora_id' => 'nullable|exists:apprevendedora,id',
            'supervisor_id'  => 'nullable|integer',
            'status'         => 'nullable|string|max:20',
        ]);

        // Garante que ninguém troca a empresa da equipe via request
        unset($dados['empresa_id']);

        $equipe->update($dados);

        return redirect()
            ->route('equiperevenda.index')
            ->with('success', 'Equipe atualizada com sucesso!');
    }

    public function destroy(Request $request, $id)
    {
        $equipe = EquipeRevenda::findOrFail($id);
        $this->autorizarEmpresa($request, $equipe);

        $equipe->delete();

        return redirect()
            ->route('equiperevenda.index')
            ->with('success', 'Equipe excluída com sucesso!');
    }
}
