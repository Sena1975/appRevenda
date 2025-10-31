<?php

namespace App\Http\Controllers;

use App\Models\PedidoCompra;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\ItensCompra;
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
    DB::beginTransaction();

    try {
        // Cabeçalho da compra
        $pedido = PedidoCompra::create([
            'fornecedor_id' => $request->fornecedor_id,
            'data_compra' => now(),
            'numpedcompra' => $request->numpedcompra,
            'valor_total' => 0,
            'preco_venda_total' => 0,
            'formapgto' => $request->formapgto,
            'qt_parcelas' => $request->qt_parcelas ?? 1,
            'status' => 'PENDENTE',
        ]);

        $totalCompra = 0;
        $totalVenda = 0;

        // Itens da compra
        if ($request->itens && is_array($request->itens)) {
            foreach ($request->itens as $item) {
                $precoCompra = floatval($item['preco_unitario']);
                $precoVenda = floatval($item['preco_venda_unitario'] ?? 0);
                $quantidade = floatval($item['quantidade']);

                ItensCompra::create([
                    'compra_id' => $pedido->id,
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoCompra,
                    'preco_venda_unitario' => $precoVenda,
                    'total_item' => $quantidade * $precoCompra,
                    'total_venda' => $quantidade * $precoVenda,
                    'pontos' => $item['pontos'] ?? 0,
                    'qtd_disponivel' => $quantidade,
                ]);

                $totalCompra += $quantidade * $precoCompra;
                $totalVenda += $quantidade * $precoVenda;
            }
        }

        // Atualiza o total no cabeçalho
        $pedido->update([
            'valor_total' => $totalCompra,
            'preco_venda_total' => $totalVenda,
        ]);

        DB::commit();

        return redirect()->route('compras.index')->with('success', 'Pedido de compra cadastrado com sucesso!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Erro ao salvar compra: ' . $e->getMessage());
    }
    dd($request->all());
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
