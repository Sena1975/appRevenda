<?php

namespace App\Http\Controllers;

use App\Models\Revendedora;
use Illuminate\Http\Request;

class RevendedoraController extends Controller
{
    public function index()
    {
        $revendedoras = Revendedora::orderBy('nome')->paginate(10);
        return view('revendedoras.index', compact('revendedoras'));
    }

    public function create()
    {
        return view('revendedoras.create');
    }

    public function store(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario?->empresa_id;

        $validated = $request->validate([
            'nome'           => 'required|string|max:255',
            'cpf'            => 'required|string|max:14|unique:apprevendedora,cpf',
            // demais campos opcionais…
            'status'         => 'nullable|integer',     // <<< antes era required
            'revenda_padrao' => 'nullable|boolean',
        ]);

        // defaults seguros no backend
        $validated['status']         = (int) ($request->input('status', 1));         // default 1
        $validated['revenda_padrao'] = $request->boolean('revenda_padrao') ? 1 : 0;  // default 0
        $validated['empresa_id'] = $empresaId;

        $rev = \App\Models\Revendedora::create($validated);

        if ($rev->revenda_padrao) {
            \App\Models\Revendedora::where('id', '!=', $rev->id)->update(['revenda_padrao' => 0]);
        }

        return redirect()->route('revendedoras.index')->with('success', 'Revendedora cadastrada com sucesso!');
    }

    public function edit($id)
    {
        $revendedora = Revendedora::findOrFail($id);
        return view('revendedoras.edit', compact('revendedora'));
    }

    public function update(Request $request, $id)
    {
        $revendedora = \App\Models\Revendedora::findOrFail($id);

        $validated = $request->validate([
            'nome'           => 'required|string|max:255',
            'cpf'            => 'required|string|max:14|unique:apprevendedora,cpf,' . $revendedora->id,
            // demais campos opcionais…
            'status'         => 'nullable|integer',     // <<< antes era possibly required
            'revenda_padrao' => 'nullable|boolean',
        ]);

        $validated['status']         = (int) ($request->input('status', $revendedora->status ?? 1));
        $validated['revenda_padrao'] = $request->boolean('revenda_padrao') ? 1 : 0;

        $revendedora->update($validated);

        if ($revendedora->revenda_padrao) {
            \App\Models\Revendedora::where('id', '!=', $revendedora->id)->update(['revenda_padrao' => 0]);
        }

        return redirect()->route('revendedoras.index')->with('success', 'Dados atualizados com sucesso!');
    }
    public function destroy($id)
    {
        $revendedora = Revendedora::findOrFail($id);
        $revendedora->delete();

        return redirect()->route('revendedoras.index')
            ->with('success', 'Revendedora excluída com sucesso!');
    }
}
