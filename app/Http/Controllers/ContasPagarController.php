<?php

namespace App\Http\Controllers;

use App\Models\ContasPagar;
use App\Models\Fornecedor;
use App\Models\BaixaPagar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class ContasPagarController extends Controller
{
    /**
     * Descobre o ID da empresa atual.
     */
    private function getEmpresaId(): ?int
    {
        $user    = Auth::user(); 
        $empresa = $user?->empresa;

        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        return $empresa?->id;
    }

    /**
     * Lista de contas a pagar com filtros.
     */
    public function index(Request $request)
    {
        $empresaId = $this->getEmpresaId();

        $query = ContasPagar::with(['fornecedor', 'compra'])
            ->orderBy('data_vencimento')
            ->orderBy('parcela');

        // 🔹 Filtro por empresa
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        // Filtro por fornecedor
        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        // Filtro por status
        if ($request->filled('status') && in_array($request->status, ['ABERTO', 'PAGO', 'CANCELADO'])) {
            $query->where('status', $request->status);
        }

        // Filtro por período de vencimento
        if ($request->filled('data_ini')) {
            $query->whereDate('data_vencimento', '>=', $request->data_ini);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_vencimento', '<=', $request->data_fim);
        }

        $contas = $query->get();

        // Resumos
        $totalAberto = $contas->where('status', 'ABERTO')->sum('valor');
        $totalPago   = $contas->where('status', 'PAGO')->sum('valor_pago');
        $totalGeral  = $contas->sum('valor');

        $resumo = [
            'total_aberto' => $totalAberto,
            'total_pago'   => $totalPago,
            'total_geral'  => $totalGeral,
        ];

        // Fornecedores da empresa
        $fornecedoresQuery = Fornecedor::orderBy('nomefantasia');
        if ($empresaId && $fornecedoresQuery->getModel()->isFillable('empresa_id')) {
            $fornecedoresQuery->where('empresa_id', $empresaId);
        }
        $fornecedores = $fornecedoresQuery->get();

        return view('contaspagar.index', [
            'contas'       => $contas,
            'fornecedores' => $fornecedores,
            'resumo'       => $resumo,
            'filtros'      => $request->only(['fornecedor_id', 'status', 'data_ini', 'data_fim']),
        ]);
    }

    /**
     * Tela de edição dos dados da conta (vencimento, nota, observação).
     */
    public function edit($id)
    {
        $empresaId = $this->getEmpresaId();

        $conta = ContasPagar::with(['fornecedor', 'compra', 'baixas'])
            ->where('id', $id)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->firstOrFail();

        if ($conta->status === 'PAGO' || $conta->status === 'CANCELADO') {
            return redirect()
                ->route('contaspagar.index')
                ->with('error', 'Não é possível editar uma conta já paga ou cancelada. Use estorno, se necessário.');
        }

        $totalBaixado = $conta->baixas->sum('valor_baixado');
        $saldo        = $conta->valor - $totalBaixado;

        return view('contaspagar.edit', [
            'conta'        => $conta,
            'totalBaixado' => $totalBaixado,
            'saldo'        => $saldo,
        ]);
    }

    /**
     * Salva alterações de vencimento/nota/observação.
     */
    public function update(Request $request, $id)
    {
        $empresaId = $this->getEmpresaId();

        $conta = ContasPagar::where('id', $id)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->firstOrFail();

        $data = $request->validate([
            'data_vencimento' => 'required|date',
            'numero_nota'     => 'nullable|string|max:50',
            'observacao'      => 'nullable|string',
        ]);

        $conta->data_vencimento = $data['data_vencimento'];
        $conta->numero_nota     = $data['numero_nota'] ?? $conta->numero_nota;
        $conta->observacao      = $data['observacao'] ?? $conta->observacao;

        $conta->save();

        return redirect()
            ->route('contaspagar.index')
            ->with('success', 'Dados da conta atualizados com sucesso.');
    }

    /**
     * Tela somente para registrar baixa (pagamento).
     */
    public function formBaixa($id)
    {
        $empresaId = $this->getEmpresaId();

        $conta = ContasPagar::with(['fornecedor', 'compra', 'baixas'])
            ->where('id', $id)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->firstOrFail();

        if ($conta->status !== 'ABERTO') {
            return redirect()
                ->route('contaspagar.index')
                ->with('error', 'Só é possível registrar baixa em contas em aberto.');
        }

        $totalBaixado = $conta->baixas->sum('valor_baixado');
        $saldo        = $conta->valor - $totalBaixado;

        return view('contaspagar.baixar', [
            'conta'        => $conta,
            'baixas'       => $conta->baixas->sortByDesc('data_baixa'),
            'totalBaixado' => $totalBaixado,
            'saldo'        => $saldo,
        ]);
    }

    /**
     * Registra uma nova baixa na conta.
     */
    public function baixar(Request $request, $id)
    {
        $empresaId = $this->getEmpresaId();

        $conta = ContasPagar::with('baixas')
            ->where('id', $id)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->firstOrFail();

        $data = $request->validate([
            'data_baixa'      => 'required|date',
            'valor_baixado'   => 'required|numeric|min:0.01',
            'forma_pagamento' => 'required|string|max:50',
            'observacao'      => 'nullable|string',
        ]);

        return DB::transaction(function () use ($conta, $data) {

            $totalBaixadoAnterior = $conta->baixas()->sum('valor_baixado');
            $saldoAnterior        = $conta->valor - $totalBaixadoAnterior;

            if ($data['valor_baixado'] > $saldoAnterior + 0.01) {
                return back()
                    ->withInput()
                    ->with('error', 'Valor da baixa não pode ser maior que o saldo em aberto.');
            }

            // 1) Grava a nova baixa
            BaixaPagar::create([
                'conta_id'        => $conta->id,
                'numero_nota'     => $conta->numero_nota,
                'parcela'         => $conta->parcela,
                'data_baixa'      => $data['data_baixa'],
                'valor_baixado'   => $data['valor_baixado'],
                'forma_pagamento' => $data['forma_pagamento'],
                'observacao'      => $data['observacao'] ?? null,
                'recibo_enviado'  => false,
            ]);

            // 2) Recalcula total baixado e saldo
            $totalBaixado = $conta->baixas()->sum('valor_baixado');
            $saldo        = $conta->valor - $totalBaixado;

            // 3) Atualiza a conta
            $conta->valor_pago     = $totalBaixado > 0 ? $totalBaixado : null;
            $conta->data_pagamento = $totalBaixado > 0 ? $data['data_baixa'] : null;

            if ($saldo <= 0.009) {
                $conta->status = 'PAGO';
            } else {
                $conta->status = 'ABERTO';
            }

            $conta->save();

            return redirect()
                ->route('contaspagar.index')
                ->with('success', 'Baixa registrada com sucesso!');
        });
    }

    public function estornar($id)
    {
        $empresaId = $this->getEmpresaId();

        $conta = ContasPagar::with('baixas')
            ->where('id', $id)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->firstOrFail();

        if ($conta->status !== 'PAGO') {
            return redirect()
                ->route('contaspagar.index')
                ->with('error', 'Só é possível estornar contas com status PAGO.');
        }

        return DB::transaction(function () use ($conta) {

            // Apaga todas as baixas dessa conta
            foreach ($conta->baixas as $baixa) {
                $baixa->delete();
            }

            // Zera informações de pagamento na conta
            $conta->status         = 'ABERTO';
            $conta->valor_pago     = null;
            $conta->data_pagamento = null;
            $conta->save();

            return redirect()
                ->route('contaspagar.index')
                ->with('success', 'Pagamento estornado com sucesso! A conta voltou para ABERTO.');
        });
    }
}
