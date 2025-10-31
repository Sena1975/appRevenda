<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ContasReceber;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class BaixaReceberController extends Controller
{
    /**
     * Exibe formulário de baixa de uma conta
     */
    public function create($id)
    {
        $conta = DB::table('appcontasreceber')->find($id);

        if (!$conta) {
            return redirect()->route('contas.index')->with('error', 'Conta não encontrada.');
        }

        return view('financeiro.baixa', compact('conta'));
    }

    /**
     * Efetua a baixa e gera recibo em PDF
     */
    public function store(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $conta = DB::table('appcontasreceber')->where('id', $id)->first();

            if (!$conta) {
                throw new \Exception('Conta não encontrada.');
            }

            if ($conta->status === 'PAGO') {
                throw new \Exception('Esta conta já está quitada.');
            }

            $valorBaixa = $request->valor_baixado ?? $conta->valor;

            // Insere registro de baixa
            $baixaId = DB::table('appbaixa_receber')->insertGetId([
                'conta_id'       => $conta->id,
                'data_baixa'     => Carbon::now(),
                'valor_baixado'  => $valorBaixa,
                'forma_pagamento'=> $request->forma_pagamento,
                'observacao'     => $request->observacao,
                'recibo_enviado' => 0,
                'created_at'     => Carbon::now()
            ]);

            // Atualiza status da conta
            DB::table('appcontasreceber')
                ->where('id', $conta->id)
                ->update([
                    'status' => 'PAGO',
                    'data_pagamento' => Carbon::now(),
                    'valor_pago' => $valorBaixa,
                    'updated_at' => Carbon::now()
                ]);

            DB::commit();

            // 🔹 GERA RECIBO PDF
            $pdf = PDF::loadView('financeiro.recibo', [
                'conta' => $conta,
                'valor' => $valorBaixa,
                'data_baixa' => Carbon::now()->format('d/m/Y'),
                'forma_pagamento' => $request->forma_pagamento,
            ]);

            $arquivo = 'recibo_'.$conta->id.'_'.time().'.pdf';
            $pdf->save(storage_path('app/public/'.$arquivo));

            /*
            // 🔜 FUTURO: envio automático pelo WhatsApp (BotConversa / Z-API)
            $cliente = DB::table('appcliente')->find($conta->cliente_id);
            $mensagem = "Olá, {$cliente->nome}! Confirmamos o pagamento do seu pedido #{$conta->pedido_id}.";
            app(BotConversaService::class)->enviarArquivo($cliente->telefone, storage_path('app/public/'.$arquivo), $mensagem);
            */

            return redirect()->route('contas.index')->with('success', 'Baixa registrada e recibo gerado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao registrar baixa: '.$e->getMessage());
        }
    }
}
