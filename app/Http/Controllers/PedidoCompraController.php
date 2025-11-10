<?php

namespace App\Http\Controllers;

use App\Models\PedidoCompra;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\ItensCompra;
use App\Models\ViewProduto;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EstoqueService;
use Illuminate\Support\Facades\Log;


class PedidoCompraController extends Controller
{
    /**
     * Exibe a lista de pedidos de compra
     */
    public function index()
    {
        $pedidos = PedidoCompra::with('fornecedor')->orderBy('id', 'desc')->get();
        return view('compras.index', compact('pedidos'));
    }

    /**
     * Mostra o formulário de nova compra
     */
    public function create()
    {
        $fornecedores = Fornecedor::orderBy('razaosocial')->get();
        $produtos = Produto::orderBy('nome')->get();

        return view('compras.create', compact('fornecedores', 'produtos'));
    }

    /**
     * Grava o pedido de compra e seus itens
     */
public function store(Request $request)
    {
        $data = $request->validate([
            'fornecedor_id'       => 'required|integer',
            'data_pedido'         => 'required|date',
            'observacao'          => 'nullable|string|max:1000',
            'desconto'            => 'nullable|numeric|min:0',

            'itens'               => 'required|array|min:1',
            'itens.*.produto_id'  => 'required|integer',
            'itens.*.codfabnumero'=> 'nullable|string',
            'itens.*.quantidade'  => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {

            $totalBruto = 0.0;
            $totalPontosUnit = 0;
            $totalPontosGeral = 0;

            $itensCalc = [];

            foreach ($data['itens'] as $idx => $item) {

                $codfab = $item['codfabnumero'] ?? null;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (!$vp) {
                    abort(422, "Produto inválido na linha ".($idx+1)." (código não encontrado na view).");
                }

                // COMPRA -> usa preço de compra
                $qtd           = (int) $item['quantidade'];
                $precoUnit     = (float) $vp->preco_compra;   // <- PREÇO OFICIAL de compra
                $pontosUnit    = (int) $vp->pontos;           // se quiser considerar pontos já na compra
                $estoqueAtual  = (int) $vp->qtd_estoque;

                $totalLinha    = $qtd * $precoUnit;
                $pontosLinha   = $qtd * $pontosUnit;

                $totalBruto       += $totalLinha;
                $totalPontosUnit  += $pontosUnit;
                $totalPontosGeral += $pontosLinha;

                $itensCalc[] = [
                    'produto_id'       => $item['produto_id'],
                    'codfabnumero'     => $codfab ?? $vp->codigo_fabrica,
                    'descricao'        => $vp->descricao_produto,
                    'quantidade'       => $qtd,
                    'preco_unitario'   => $precoUnit,
                    'total'            => $totalLinha,
                    'pontuacao'        => $pontosUnit,
                    'pontuacao_total'  => $pontosLinha,
                    'estoque_atual'    => $estoqueAtual,
                ];
            }

            $desconto     = (float) ($data['desconto'] ?? 0);
            $totalLiquido = max(0, $totalBruto - $desconto);

            // Cabeçalho
            $compraId = DB::table('compras')->insertGetId([
                'fornecedor_id'   => $data['fornecedor_id'],
                'data_pedido'     => $data['data_pedido'],
                'observacao'      => $data['observacao'] ?? null,
                'total_bruto'     => $totalBruto,
                'desconto'        => $desconto,
                'total_liquido'   => $totalLiquido,
                'pontuacao'       => $totalPontosUnit,
                'pontuacao_total' => $totalPontosGeral,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Itens
            foreach ($itensCalc as $it) {
                DB::table('compra_itens')->insert([
                    'compra_id'        => $compraId,
                    'produto_id'       => $it['produto_id'],
                    'codfabnumero'     => $it['codfabnumero'],
                    'descricao'        => $it['descricao'],
                    'quantidade'       => $it['quantidade'],
                    'preco_unitario'   => $it['preco_unitario'],
                    'total'            => $it['total'],
                    'pontuacao'        => $it['pontuacao'],
                    'pontuacao_total'  => $it['pontuacao_total'],
                    'estoque_atual'    => $it['estoque_atual'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // (Opcional) Só daremos entrada em estoque no item (2).
            return redirect()
                ->route('compras.index')
                ->with('success', 'Compra salva com sucesso (totais recalculados no servidor).');
        });
    }


    /**
     * Exibe os detalhes da compra
     */
    public function show($id)
    {
        $pedido = PedidoCompra::with(['fornecedor', 'itens.produto'])->findOrFail($id);
        return view('compras.show', compact('pedido'));
    }

    /**
     * Edita o pedido (ex: confirmar recebimento)
     */
public function edit($id)
{
    $pedido = PedidoCompra::with(['itens.produto', 'fornecedor'])->findOrFail($id);

    $fornecedores = Fornecedor::orderBy('nomefantasia')->get();
    $produtos = Produto::orderBy('nome')->get();

    return view('compras.edit', [
        'pedido' => $pedido,
        'fornecedores' => $fornecedores,
        'produtos' => $produtos,
        'numeroPedido' => $pedido->numpedcompra ?? '(sem número)'
    ]);
}


    /**
     * Atualiza a compra
     */
public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $pedido = PedidoCompra::with(['itens.produto'])->findOrFail($id);

        // Atualiza cabeçalho
        $pedido->update([
            'numpedcompra' => $request->numpedcompra,
            'valor_total' => $request->valor_total,
            'preco_venda_total' => $request->preco_venda_total,
            'status' => $request->acao === 'confirmar' ? 'RECEBIDA' : 'PENDENTE',
        ]);

        // Atualiza os itens (usando produto_id como referência)
        foreach ($request->itens as $dados) {
            $item = $pedido->itens->where('produto_id', $dados['produto_id'])->first();

            if ($item) {
                $item->update([
                    'qtd_disponivel'       => ($dados['quantidade'] ?? $item->quantidade),
                    'preco_unitario'       => ($dados['preco_unitario'] ?? $item->preco_unitario),
                    'preco_venda_unitario' => ($dados['preco_venda_unitario'] ?? $item->preco_venda_unitario),
                    'pontos'               => ($dados['pontos'] ?? $item->pontos),
                ]);
            }
        }

        // Se confirmar recebimento, registra movimentação no estoque
        if ($request->acao === 'confirmar') {
            $service = new \App\Services\EstoqueService();
            $service->registrarEntradaCompra($pedido);
        }

        DB::commit();

        $msg = $request->acao === 'confirmar'
            ? 'Recebimento confirmado e estoque atualizado!'
            : 'Pedido salvo com sucesso!';

        return redirect()->route('compras.index')->with('success', $msg);

    } catch (\Exception $e) {
    DB::rollBack();

    log::error('Erro ao atualizar pedido de compra', [
        'mensagem' => $e->getMessage(),
        'linha'    => $e->getLine(),
        'arquivo'  => $e->getFile(),
    ]);

    return back()->with('error', 'Erro ao atualizar o pedido: ' . $e->getMessage());
    }
}


        /**
         * Exibe o formulário de importação de itens
         */
        public function importarItens($id)
        {
            $pedido = PedidoCompra::findOrFail($id);
            return view('compras.importar', compact('pedido'));
        }

        /**
         * Processa o upload e importa os itens (CSV simples)
         */
        public function processarImportacao(Request $request, $id)
        {
            $request->validate([
                'arquivo' => 'required|file|mimes:csv,txt|max:2048',
            ]);

            $pedido = PedidoCompra::findOrFail($id);

            $linhas = array_map('str_getcsv', file($request->file('arquivo')->getRealPath()));

            $importados = 0;
            foreach ($linhas as $linha) {
                // Espera formato: CODFAB;QUANTIDADE
                [$codfab, $qtd] = $linha;

                $produto = \App\Models\Produto::where('codfab', trim($codfab))->first();
                if ($produto) {
                    \App\Models\ItensCompra::create([
                        'compra_id' => $pedido->id,
                        'produto_id' => $produto->id,
                        'quantidade' => floatval($qtd),
                        'preco_unitario' => $produto->preco ?? 0,
                        'qtd_disponivel' => $qtd,
                    ]);
                    $importados++;
                }
            }

            return redirect()->route('compras.edit', $pedido->id)
                ->with('success', "Importação concluída: {$importados} itens adicionados.");
        }
        /**
         * Exporta os itens do pedido de compra em formato CSV
         */
        public function exportarItens($id)
        {
            $pedido = PedidoCompra::with(['itens.produto'])->findOrFail($id);

            $filename = 'pedido_compra_' . $pedido->id . '.csv';

            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, ['Codigo', 'Quantidade'], ';');

            foreach ($pedido->itens as $item) {
                fputcsv($handle, [
                    $item->produto->codfab ?? '',
                    intval($item->quantidade),
                ], ';');
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename={$filename}");
        }

    /**
     * Remove um pedido
     */

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $pedido = PedidoCompra::with('itens.produto')->findOrFail($id);
            $motivo = $request->motivo_cancelamento ?? 'Sem motivo informado';

            // Se o pedido foi recebido, faz o estorno
            if ($pedido->status === 'RECEBIDA') {
                $service = new \App\Services\EstoqueService();
                $service->estornarEntradaCompra($pedido, $motivo);
            }

            // Exclui itens e o pedido
            foreach ($pedido->itens as $item) {
                $item->delete();
            }

            $pedido->delete();

            DB::commit();

            return redirect()
                ->route('compras.index')
                ->with('success', 'Pedido cancelado e estoque estornado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao cancelar pedido: ' . $e->getMessage());
        }
    }

}
