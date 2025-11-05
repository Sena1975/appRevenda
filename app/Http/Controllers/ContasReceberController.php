<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContasReceberController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('appcontasreceber as cr')
            ->leftJoin('appcliente as cli', 'cli.id', '=', 'cr.cliente_id')
            ->leftJoin('apprevendedora as rev', 'rev.id', '=', 'cr.revendedora_id')
            ->selectRaw('cr.*, cli.nome as cliente_nome, rev.nome as revendedora_nome')
            ->orderByDesc('cr.id');

        // Filtros
        $clienteId  = $request->input('cliente_id');
        $clienteTxt = trim((string)$request->input('cliente', ''));

        if ($clienteId) {
            $q->where('cr.cliente_id', (int) $clienteId);
        } elseif ($clienteTxt !== '') {
            if (is_numeric($clienteTxt)) {
                $q->where('cr.cliente_id', (int) $clienteTxt);
            } else {
                $q->where('cli.nome', 'like', "%{$clienteTxt}%");
            }
        }

        $status = strtoupper((string)$request->input('status', 'TODOS'));
        if ($status !== '' && $status !== 'TODOS') {
            $q->whereRaw('UPPER(cr.status) = ?', [$status]);
        }

        $dataDe  = $request->input('data_de',  $request->input('data_ini'));
        $dataAte = $request->input('data_ate', $request->input('data_fim'));

        if (!empty($dataDe))  $q->whereDate('cr.data_vencimento', '>=', $dataDe);
        if (!empty($dataAte)) $q->whereDate('cr.data_vencimento', '<=', $dataAte);

        $contas = $q->paginate(15)->appends($request->query());

        // Compat p/ views
        $contas->getCollection()->transform(function ($c) {
            $c->vencimento = $c->data_vencimento;
            $c->pago_em    = $c->data_pagamento;
            if (strtoupper((string)$c->status) === 'PAGO') {
                $c->status = 'PAGO';
            }
            return $c;
        });

        $filtros = [
            'cliente'    => $clienteTxt,
            'cliente_id' => $clienteId,
            'status'     => $status ?: 'TODOS',
            'data_de'    => $dataDe ?? '',
            'data_ate'   => $dataAte ?? '',
        ];

        $clientes = DB::table('appcliente')->orderBy('nome')->get(['id','nome']);

        return view('Financeiro.index', compact('contas', 'filtros', 'clientes'));
    }

    public function show($id)
    {
        $c = DB::table('appcontasreceber as cr')
            ->leftJoin('appcliente as cli', 'cli.id', '=', 'cr.cliente_id')
            ->leftJoin('apprevendedora as rev', 'rev.id', '=', 'cr.revendedora_id')
            ->selectRaw('cr.*, cli.nome as cliente_nome, rev.nome as revendedora_nome')
            ->where('cr.id', $id)
            ->first();

        abort_if(!$c, 404);

        return view('Financeiro.show', compact('c'));
    }

    /** GET: editar (campos básicos; baixa NÃO é aqui) */
    public function edit($id)
    {
        $c = DB::table('appcontasreceber')->where('id', $id)->first();
        abort_if(!$c, 404);

        $clientes = DB::table('appcliente')->orderBy('nome')->get(['id','nome']);
        $formas   = DB::table('appformapagamento')->orderBy('nome')->get(['id','nome']);

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
        ],[
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

    /** GET: form de baixa */
    public function baixar($id)
    {
        $c = DB::table('appcontasreceber')->where('id', $id)->first();
        abort_if(!$c, 404);

        if (strtoupper((string)$c->status) === 'PAGO') {
            return redirect()->route('contasreceber.index')
                ->with('info', 'Esta parcela já está baixada.');
        }

        return view('Financeiro.baixa', ['conta' => $c]);
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
 public function recibo(Request $request, string $id)
{
    // carrega a parcela com joins úteis para o recibo
    $c = DB::table('appcontasreceber as cr')
        ->leftJoin('appcliente as cli', 'cli.id', '=', 'cr.cliente_id')
        ->leftJoin('appformapagamento as f', 'f.id', '=', 'cr.forma_pagamento_id')
        ->selectRaw("
            cr.*,
            cli.nome  as cliente_nome,
            f.nome    as forma_nome
        ")
        ->where('cr.id', (int)$id)
        ->first();

    if (!$c) {
        return redirect()->route('contasreceber.index')->with('error', 'Conta não encontrada.');
    }

    // variáveis usadas na view
    $recibo_numero = str_pad((string)$c->id, 6, '0', STR_PAD_LEFT); // ex.: 000123
    $statusUpper   = strtoupper((string)($c->status ?? ''));

    $data = compact('c', 'recibo_numero', 'statusUpper');

    // Se pediu PDF (?pdf=1), tenta gerar com DomPDF
    if ($request->boolean('pdf')) {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('Financeiro.recibo', $data)
                ->setPaper('a4', 'portrait');

            return $pdf->stream("recibo_{$recibo_numero}.pdf"); // abre no navegador
            // ->download("recibo_{$recibo_numero}.pdf"); // se preferir forçar download
        }

        // Pacote não instalado
        return redirect()
            ->route('contasreceber.recibo', ['id' => $id])
            ->with('info', 'Pacote de PDF não encontrado. Exibindo recibo em HTML.');
    }

    // HTML normal
    return view('Financeiro.recibo', $data);
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
