<?php

namespace App\Services\Importacao;

class PedidoWhatsappParser
{
    /**
     * Exemplo de linha:
     * "1 Unidade(s) - Código: 6587 - Preço: R$ 25,90 - Batom color tint FPS 8"
     */
    public function parse(string $texto): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $itens = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '' || stripos($linha, 'Total =') !== false) {
                continue;
            }

            // quantidade
            if (!preg_match('/(\d+)\s+Unidade/i', $linha, $mQtd)) {
                continue;
            }
            $quantidade = (int) ($mQtd[1] ?? 0);

            // código
            if (!preg_match('/Código:\s*([\d]+)/i', $linha, $mCod)) {
                continue;
            }
            $codigo = trim($mCod[1]);

            // preço
            $preco = null;
            if (preg_match('/Preço:\s*R\$\s*([\d\.,]+)/i', $linha, $mPre)) {
                $precoStr = str_replace(['.', ','], ['', '.'], $mPre[1]); // "2.464,50" -> "2464.50"
                $preco = (float) $precoStr;
            }

            // descrição (depois do último "-")
            $descricao = null;
            $partes = explode('-', $linha);
            if (count($partes) >= 2) {
                $descricao = trim(end($partes));
            }

            $itens[] = [
                'codigo'     => $codigo,
                'quantidade' => $quantidade,
                'preco'      => $preco,
                'descricao'  => $descricao,
            ];
        }

        return $itens;
    }
}
