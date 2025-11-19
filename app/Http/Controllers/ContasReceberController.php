<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContasReceberController extends Controller
{
    public function index(Request $request)
    {
        // base: VIEW, não mais tabela bruta
        $q = DB::table('view_app_contasreceber as v')
            ->orderByDesc('v.conta_id');

        // ---------------- Filtros ----------------

        // Cliente (texto ou ID numérico)
        $clienteTxt = trim((string) $request->input('cliente', ''));

        if ($clienteTxt !== '') {
            if (is_numeric($clienteTxt)) {
                $q->where('v.cliente_id', (int) $clienteTxt);
            } else {
                $q->where('v.cliente_nome', 'like', "%{$clienteTxt}%");
            }
        }

        // Status (ABERTO, PAGO, CANCELADO, TODOS)
        $status = strtoupper((string) $request->input('status', 'TODOS'));
        if ($status !== '' && $status !== 'TODOS') {
            $q->whereRaw('UPPER(v.status_titulo) = ?', [$status]);
        }

        // Período de vencimento (nome igual ao do form: dt_ini / dt_fim)
        $dataIni = $request->input('dt_ini');
        $dataFim = $request->input('dt_fim');

        if (!empty($dataIni)) {
            $q->whereDate('v.data_vencimento', '>=', $dataIni);
        }
        if (!empty($dataFim)) {
            $q->whereDate('v.data_vencimento', '<=', $dataFim);
        }

        // ---------------- Select / paginação ----------------
        $contas = $q->select([
            'v.conta_id         as id',
            'v.cliente_nome',
            'v.parcela',
            'v.total_parcelas',
            'v.data_vencimento',
            'v.status_titulo    as status',
            'v.valor_titulo     as valor',
            'v.forma_pagamento',
            'v.plano_pagamento',
        ])
            ->paginate(15)
            ->appends($request->query());

        // Compat com a view (usa $c->vencimento)
        $contas->getCollection()->transform(function ($c) {
            $c->vencimento = $c->data_vencimento;
            return $c;
        });

        // Array de filtros esperado pela Blade (usa $filtro, não $filtros)
        $filtro = [
            'cliente' => $clienteTxt,
            'status'  => $status ?: 'TODOS',
            'dt_ini'  => $dataIni ?? '',
            'dt_fim'  => $dataFim ?? '',
        ];

        // (Se quiser aproveitar lista de clientes depois, já deixo aqui)
        $clientes = DB::table('appcliente')->orderBy('nome')->get(['id', 'nome']);

        return view('contasreceber.index', compact('contas', 'filtro', 'clientes'));
    }
    public function show($id)
    {
        $c = DB::table('view_app_contasreceber as v')
            ->where('v.conta_id', (int) $id)
            ->select([
                'v.conta_id           as id',
                'v.pedido_id',
                'v.cliente_id',
                'v.cliente_nome',
                'v.revendedora_id',
                'v.revendedora_nome',
                'v.parcela',
                'v.total_parcelas',
                'v.forma_pagamento',
                'v.plano_pagamento',
                'v.data_emissao',
                'v.data_vencimento',
                'v.valor_titulo       as valor',
                'v.status_titulo      as status',
                'v.data_pagamento',
                'v.valor_pago',
                'v.saldo',
                'v.situacao',
            ])
            ->first();

        abort_if(!$c, 404);

        // compat com nomes usados na view
        $c->vencimento = $c->data_vencimento;

        return view('contasreceber.show', compact('c'));
    }

    /** GET: editar (campos básicos; baixa NÃO é aqui) */
    public function edit($id)
    {
        $c = DB::table('appcontasreceber')->where('id', $id)->first();
        abort_if(!$c, 404);

        $clientes = DB::table('appcliente')->orderBy('nome')->get(['id', 'nome']);
        $formas   = DB::table('appformapagamento')->orderBy('nome')->get(['id', 'nome']);

        return view('Financeiro.edit', compact('c', 'clientes', 'formas'));
    }

    /** PUT/PATCH: salvar alterações básicas */
    public function update(Request $request, $id)
    {
        $request->validate([
            'cliente_id'         => 'required|integer|exists:appcliente,id',
            'forma_pagamento_id' => 'required|integer|exists:appformapagamento,id',
            'data_vencimento'    => 'required|date',
            'valor'              => 'required',
            'observacao'         => 'nullable|string',
        ], [
            'cliente_id.required' => 'Selecione o cliente.',
            'forma_pagamento_id.required' => 'Selecione a forma de pagamento.',
            'data_vencimento.required' => 'Informe a data de vencimento.',
            'valor.required' => 'Informe o valor.',
        ]);

        $valor = $this->brToDecimal($request->valor);

        // Não permitimos transformar em PAGO por aqui (use a BAIXA)
        $statusAtual = DB::table('appcontasreceber')->where('id', $id)->value('status') ?? 'ABERTO';

        DB::table('appcontasreceber')->where('id', $id)->update([
            'cliente_id'         => (int)$request->cliente_id,
            'forma_pagamento_id' => (int)$request->forma_pagamento_id,
            'data_vencimento'    => $request->data_vencimento,
            'valor'              => $valor,
            'observacao'         => $request->observacao,
            'status'             => $statusAtual, // preserva; baixa/estorno controlam mudanças
            'atualizado_em'      => now(),
        ]);

        return redirect()->route('contasreceber.show', $id)
            ->with('success', 'Parcela atualizada com sucesso.');
    }

public function baixar($id)
{
    // Carrega pela VIEW para ter cliente_nome, forma, etc.
    $c = DB::table('view_app_contasreceber as v')
        ->where('v.conta_id', (int)$id)
        ->select([
            'v.conta_id        as id',
            'v.pedido_id',
            'v.cliente_id',
            'v.cliente_nome',
            'v.revendedora_id',
            'v.revendedora_nome',
            'v.forma_pagamento',
            'v.plano_pagamento',
            'v.data_emissao',
            'v.data_vencimento',
            'v.valor_titulo    as valor',
            'v.status_titulo   as status',
            'v.data_pagamento',
            'v.valor_pago',
            'v.saldo',
            'v.situacao',
        ])
        ->first();

    abort_if(!$c, 404);

    if (strtoupper((string)$c->status) === 'PAGO') {
        return redirect()
            ->route('contasreceber.index')
            ->with('info', 'Esta parcela já está baixada.');
    }

    return view('financeiro.baixa', ['conta' => $c]);
}

    /** POST: efetiva a baixa */
    public function baixarStore(Request $request, $id)
    {
        $request->validate([
            'data_pagamento' => 'required|date',
            'valor_pago'     => 'required',
            'observacao'     => 'nullable|string',
        ]);

        $valor = $this->brToDecimal($request->valor_pago);

        DB::table('appcontasreceber')->where('id', $id)->update([
            'status'         => 'PAGO',
            'data_pagamento' => $request->data_pagamento, // coluna DATE
            'valor_pago'     => $valor,
            'observacao'     => $request->observacao,
            'atualizado_em'  => now(),
        ]);

        return redirect()->route('contasreceber.show', $id)
            ->with('success', 'Baixa registrada com sucesso.');
    }

    /** POST: estornar */
    public function estornar(Request $request, $id)
    {
        $c = DB::table('appcontasreceber')->where('id', $id)->first();
        abort_if(!$c, 404);

        if (strtoupper((string)$c->status) !== 'PAGO') {
            return back()->with('info', 'Somente parcelas baixadas podem ser estornadas.');
        }

        DB::table('appcontasreceber')->where('id', $id)->update([
            'status'         => 'ABERTO',
            'data_pagamento' => null,
            'valor_pago'     => null,
            'observacao'     => trim(($c->observacao ?? '') . "\n[ESTORNO " . now()->format('d/m/Y H:i') . "] " . ($request->observacao ?? '')),
            'atualizado_em'  => now(),
        ]);

        return redirect()->route('contasreceber.show', $id)
            ->with('success', 'Estorno realizado.');
    }

    /** Recibo */
public function recibo(Request $request, int $id)
{
    // 1) Carrega a parcela pela VIEW consolidada
    $c = DB::table('view_app_contasreceber as v')
        ->where('v.conta_id', $id)
        ->select([
            'v.conta_id        as id',
            'v.pedido_id',
            'v.cliente_id',
            'v.cliente_nome',
            'v.revendedora_id',
            'v.revendedora_nome',
            'v.parcela',
            'v.total_parcelas',
            'v.forma_pagamento',
            'v.plano_pagamento',
            'v.data_emissao',
            'v.data_vencimento',
            'v.valor_titulo    as valor_titulo',
            'v.status_titulo   as status_titulo',
            'v.data_pagamento',
            'v.valor_pago',
            'v.saldo',
            'v.situacao',
        ])
        ->first();

    if (!$c) {
        return redirect()
            ->route('contasreceber.index')
            ->with('error', 'Conta não encontrada.');
    }

    // Normaliza campos usados na view
    $c->status = $c->status_titulo ?? '';
    $c->valor  = $c->valor_titulo ?? 0.0;

    $recibo_numero = str_pad((string)$c->id, 6, '0', STR_PAD_LEFT);
    $statusUpper   = strtoupper((string)$c->status);

    // 2) Itens do pedido (produtos comprados)
    $itens = collect();
    if (!empty($c->pedido_id)) {
        $itens = DB::table('appitemvenda as iv')
            ->join('appproduto as p', 'p.id', '=', 'iv.produto_id')
            ->where('iv.pedido_id', $c->pedido_id)
            ->select([
                'iv.codfabnumero',
                'p.nome as produto_nome',
                'iv.quantidade',
                'iv.preco_unitario',
                'iv.preco_total',
            ])
            ->orderBy('p.nome')
            ->get();
    }

    $data = compact('c', 'recibo_numero', 'statusUpper', 'itens');

    // 3) Se pediu PDF (?pdf=1), usa DomPDF
    if ($request->boolean('pdf')) {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('financeiro.recibo', $data)
                ->setPaper('a4', 'portrait');

            return $pdf->stream("recibo_{$recibo_numero}.pdf");
        }

        return redirect()
            ->route('contasreceber.recibo', ['id' => $id])
            ->with('info', 'Pacote de PDF não encontrado. Exibindo recibo em HTML.');
    }

    // 4) HTML normal
    return view('financeiro.recibo', $data);
}

    /* Helpers */
    private function brToDecimal($v): float
    {
        if ($v === null) return 0.0;
        $s = str_replace([' ', '.'], ['', ''], (string)$v);
        $s = str_replace(',', '.', $s);
        return round((float)$s, 2);
    }
}
