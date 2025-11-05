<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\PlanoPagamento;
use App\Models\FormaPagamento;

class PlanoPagamentoController extends Controller
{
    /**
     * Lista de planos com a forma relacionada.
     */
    public function index()
    {
        $planos = PlanoPagamento::with('formaPagamento')
            ->orderByDesc('id')
            ->paginate(10);

        return view('planopagamento.index', compact('planos'));
    }

    /**
     * Formulário de criação.
     */
    public function create()
    {
        $formas = FormaPagamento::orderBy('nome')->get(['id','nome']);
        return view('planopagamento.create', compact('formas'));
    }

    /**
     * Salva um novo plano.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codplano'           => ['required','string','max:20', 'unique:appplanopagamento,codplano'],
            'descricao'          => ['required','string','max:100'],
            'formapagamento_id'  => ['required','integer','exists:appformapagamento,id'],
            'parcelas'           => ['nullable','integer','min:1'],
            'prazo1'             => ['nullable','integer','min:0'],
            'prazo2'             => ['nullable','integer','min:0'],
            'prazo3'             => ['nullable','integer','min:0'],
            'prazomedio'         => ['nullable','integer','min:0'],
            'ativo'              => ['nullable', 'boolean'],
        ]);

        // checkbox pode vir null => trata como 0/1
        $data['ativo'] = (int)($data['ativo'] ?? 1);

        PlanoPagamento::create($data);

        return redirect()
            ->route('planopagamento.index')
            ->with('success', 'Plano de pagamento cadastrado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit($id)
    {
        $plano  = PlanoPagamento::findOrFail($id);
        $formas = FormaPagamento::orderBy('nome')->get(['id','nome']);

        return view('planopagamento.edit', compact('plano','formas'));
    }

    /**
     * Atualiza um plano.
     */
    public function update(Request $request, $id)
    {
        $plano = PlanoPagamento::findOrFail($id);

        $data = $request->validate([
            'codplano'           => [
                'required','string','max:20',
                Rule::unique('appplanopagamento','codplano')->ignore($plano->id, 'id')
            ],
            'descricao'          => ['required','string','max:100'],
            'formapagamento_id'  => ['required','integer','exists:appformapagamento,id'],
            'parcelas'           => ['nullable','integer','min:1'],
            'prazo1'             => ['nullable','integer','min:0'],
            'prazo2'             => ['nullable','integer','min:0'],
            'prazo3'             => ['nullable','integer','min:0'],
            'prazomedio'         => ['nullable','integer','min:0'],
            'ativo'              => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = (int)($data['ativo'] ?? 1);

        $plano->update($data);

        return redirect()
            ->route('planopagamento.index')
            ->with('success', 'Plano de pagamento atualizado com sucesso.');
    }

    /**
     * Remove um plano.
     */
    public function destroy($id)
    {
        $plano = PlanoPagamento::findOrFail($id);
        $plano->delete();

        return redirect()
            ->route('planopagamento.index')
            ->with('success', 'Plano de pagamento excluído com sucesso.');
    }

    /**
     * AJAX: retorna planos por forma (shape compatível com o JS do create/edit).
     *
     * Rota: planopagamento.getByForma
     * GET /planos-por-forma/{forma_id}
     *
     * Resposta:
     * [
     *   { id, descricao, codigo, parcelas, prazo1, prazo2, prazo3 }
     * ]
     */
    public function getByForma($forma_id)
    {
        $planos = PlanoPagamento::query()
            ->where('formapagamento_id', $forma_id)
            ->where(function ($q) {
                // Se sua coluna "ativo" pode ser null, considere null como ativo
                $q->where('ativo', 1)->orWhereNull('ativo');
            })
            ->orderBy('descricao')
            ->get([
                'id',
                'descricao',
                'codplano',
                'parcelas',
                'prazo1',
                'prazo2',
                'prazo3',
            ])
            ->map(function ($p) {
                return [
                    'id'        => (int)$p->id,
                    'descricao' => $p->descricao,
                    // o front espera "codigo" — mapeamos de codplano
                    'codigo'    => $p->codplano,
                    'parcelas'  => (int)($p->parcelas ?? 0),
                    'prazo1'    => (int)($p->prazo1   ?? 0),
                    'prazo2'    => (int)($p->prazo2   ?? 0),
                    'prazo3'    => (int)($p->prazo3   ?? 0),
                ];
            })
            ->values();

        return response()->json($planos);
    }
}
