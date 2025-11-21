<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Importacao\PedidoWhatsappParser;

class TextoPedidoController extends Controller
{
    /**
     * Tela para colar o texto do pedido (WhatsApp)
     */
    public function form()
    {
        return view('tools.importar-pedido-texto');
    }

    /**
     * Recebe o texto, parseia e devolve um CSV
     * Formato: CODIGO;QUANTIDADE;PRECO_COMPRA;PONTOS;PRECO_REVENDA
     */
    public function gerarCsv(Request $request)
    {
        $request->validate([
            'texto' => 'required|string',
        ]);

        $texto = $request->input('texto');

        // Usa o service genérico
        $itens = PedidoWhatsappParser::parse($texto);

        if (empty($itens)) {
            return back()
                ->withInput()
                ->with('error', 'Nenhum item foi identificado no texto informado. Verifique o padrão da mensagem.');
        }

        // Monta o CSV em memória
        $handle = fopen('php://temp', 'r+');

        // Cabeçalho compatível com sua importação de compras
        fputcsv($handle, ['CODIGO', 'QUANTIDADE', 'PRECO_COMPRA', 'PONTOS', 'PRECO_REVENDA'], ';');

        foreach ($itens as $item) {
            $preco = $item['preco_unitario'];

            fputcsv($handle, [
                $item['codigo'],
                $item['quantidade'],
                number_format($preco, 2, ',', ''),  // PRECO_COMPRA
                0,                                  // PONTOS (por enquanto 0)
                number_format($preco, 2, ',', ''),  // PRECO_REVENDA (igual ao compra)
            ], ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $nomeArquivo = 'pedido_whatsapp_' . now()->format('Ymd_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$nomeArquivo}\"");
    }
}
