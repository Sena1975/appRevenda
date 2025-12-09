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
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;

        $planos = PlanoPagamento::with('formaPagamento')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('id')
            ->paginate(10);

        return view('planopagamento.index', compact('planos'));
    }

    /**
     * Formulário de criação.
     */
    public function create(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;

        $formas = FormaPagamento::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('planopagamento.create', compact('formas'));
    }

    /**
     * Salva um novo plano.
     */
    public function store(Request $request)
    {
        $user = $request->user();

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
        $data['ativo']      = (int)($data['ativo'] ?? 1);
        $data['empresa_id'] = $user->empresa_id ?? null;

        PlanoPagamento::create($data);

        return redirect()
            ->route('planopagamento.index')
            ->with('success', 'Plano de pagamento cadastrado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Request $request, $id)
    {
        $user  = $request->user();
        $plano = PlanoPagamento::findOrFail($id);

        if ($user && $plano->empresa_id !== $user->empresa_id) {
            abort(403, 'Este plano de pagamento não pertence à sua empresa.');
        }

        $empresaId = $user->empresa_id ?? null;

        $formas = FormaPagamento::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('planopagamento.edit', compact('plano', 'formas'));
    }

    /**
     * Atualiza um plano.
     */
    public function update(Request $request, $id)
    {
        $user  = $request->user();
        $plano = PlanoPagamento::findOrFail($id);

        if ($user && $plano->empresa_id !== $user->empresa_id) {
            abort(403, 'Este plano de pagamento não pertence à sua empresa.');
        }

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

        // não deixa mudar empresa_id por update
        unset($data['empresa_id']);

        $plano->update($data);

        return redirect()
            ->route('planopagamento.index')
            ->with('success', 'Plano de pagamento atualizado com sucesso.');
    }

    /**
     * Remove um plano.
     */
    public function destroy(Request $request, $id)
    {
        $user  = $request->user();
        $plano = PlanoPagamento::findOrFail($id);

        if ($user && $plano->empresa_id !== $user->empresa_id) {
            abort(403, 'Este plano de pagamento não pertence à sua empresa.');
        }

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
    public function getByForma(Request $request, $forma_id)
    {
        $empresaId = $request->user()->empresa_id ?? null;

        $planos = PlanoPagamento::query()
            ->where('formapagamento_id', $forma_id)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->where(function ($q) {
                // Se sua coluna "ativo" pode ser null, considera null como ativo
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
                    'id'        => (int) $p->id,
                    'descricao' => $p->descricao,
                    'codigo'    => $p->codplano, // o front espera "codigo"
                    'parcelas'  => (int) ($p->parcelas ?? 0),
                    'prazo1'    => (int) ($p->prazo1 ?? 0),
                    'prazo2'    => (int) ($p->prazo2 ?? 0),
                    'prazo3'    => (int) ($p->prazo3 ?? 0),
                ];
            })
            ->values();

        return response()->json($planos);
    }
}
