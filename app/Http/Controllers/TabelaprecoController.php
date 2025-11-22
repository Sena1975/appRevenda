<?php

namespace App\Http\Controllers;

use App\Http\Requests\TabelaPrecoRequest;
use App\Models\TabelaPreco;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelaprecoController extends Controller
{
    /**
     * Listagem com filtros e paginação
     * Filtros:
     * - busca: por nome do produto ou codfab/codfabnumero
     * - produto_id
     * - status (1 ativo, 0 inativo)
     * - vigencia: atual|futura|expirada
     */
    public function index(Request $request)
    {
        $query = TabelaPreco::query()
            ->with(['produto' => function ($q) {
                $q->select('id', 'nome', 'codfab', 'codfabnumero');
            }]);

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $query->where(function ($q) use ($busca) {
                $q->where('codfab', 'like', "%{$busca}%")
                    ->orWhereHas('produto', function ($qp) use ($busca) {
                        $qp->where('nome', 'like', "%{$busca}%")
                            ->orWhere('codfab', 'like', "%{$busca}%")
                            ->orWhere('codfabnumero', 'like', "%{$busca}%");
                    });
            });
        }

        if ($request->filled('produto_id')) {
            $query->where('produto_id', $request->produto_id);
        }

        if ($request->filled('status')) {
            $status = (int) $request->status;
            if (in_array($status, [0, 1], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('vigencia')) {
            $hoje = now()->toDateString();
            switch ($request->vigencia) {
                case 'atual':
                    $query->where('data_inicio', '<=', $hoje)
                        ->where('data_fim', '>=', $hoje);
                    break;
                case 'futura':
                    $query->where('data_inicio', '>', $hoje);
                    break;
                case 'expirada':
                    $query->where('data_fim', '<', $hoje);
                    break;
            }
        }

        $allowed = [10, 25, 50, 100];
        $porPagina = (int) $request->get('por_pagina', 10);
        if (! in_array($porPagina, $allowed)) {
            $porPagina = 10;
        }

        $tabelas = $query
            ->orderByRaw('data_inicio desc, id desc')
            ->paginate($porPagina)
            ->withQueryString();

        // Para o filtro por produto em select
        $produtos = Produto::select('id', 'nome', 'codfab', 'codfabnumero')
            ->orderBy('nome')->limit(500)->get();

        return view('tabelapreco.index', compact('tabelas', 'produtos'));
    }

    public function create()
    {
        // Sem filtros escondidos; lista produtos com codfabnumero primeiro
        $produtos = \App\Models\Produto::query()
            ->select('id', 'nome', 'codfabnumero')
            ->orderByRaw("CASE WHEN (codfabnumero IS NULL OR codfabnumero='') THEN 1 ELSE 0 END, nome ASC")
            ->get();

        return view('tabelapreco.create', compact('produtos'));
    }

    public function store(TabelaPrecoRequest $request)
    {
        TabelaPreco::create($request->validated());

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço criada com sucesso!');
    }

    public function show(TabelaPreco $tabelapreco)
    {
        $tabelapreco->load(['produto' => function ($q) {
            $q->select('id', 'nome', 'codfab', 'codfabnumero');
        }]);

        return view('tabelapreco.show', compact('tabelapreco'));
    }

    public function edit(TabelaPreco $tabelapreco)
    {
        $produtos = Produto::select('id', 'nome', 'codfab', 'codfabnumero')
            ->orderBy('nome')->limit(500)->get();

        return view('tabelapreco.edit', compact('tabelapreco', 'produtos'));
    }

    public function update(TabelaPrecoRequest $request, TabelaPreco $tabelapreco)
    {
        $tabelapreco->update($request->validated());

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço atualizada com sucesso!');
    }

    public function destroy(TabelaPreco $tabelapreco)
    {
        $tabelapreco->delete();

        return redirect()
            ->route('tabelapreco.index')
            ->with('success', 'Tabela de preço excluída com sucesso!');
    }

    /**
     * Formulário para importar tabela de preços (upload CSV).
     */
    public function formImport()
    {
        return view('tabelapreco.importar');
    }

    /**
     * Processa o arquivo CSV:
     *  - Localiza o produto pelo Cód. Fábrica (codfabnumero)
     *  - Atualiza appproduto (preco_compra, preco_revenda, pontuacao)
     *  - Faz upsert na tabelapreco (produto_id + codfab + ciclo)
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('arquivo')->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()->with('error', 'Não foi possível abrir o arquivo.');
        }

        // tenta detectar delimitador ; ou ,
        $firstLine = fgets($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        // volta para o início e lê o cabeçalho
        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter); // apenas para “queimar” o cabeçalho

        $totalLinhas   = 0;
        $atualizados   = 0;
        $naoEncontrado = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $totalLinhas++;

                // Indices conforme:
                // 0 => "Cód. Fábrica"
                // 1 => "Preço Compra"
                // 2 => "Preço Revenda"
                // 3 => "Pontuação"
                // 4 => "Data Início"
                // 5 => "Data Fim"
                // 6 => "Ciclo"
                // 7 => "CODNOTAFISCAL"   (novo)
                // 8 => "EAN"             (novo)
                $codFabrica    = trim($row[0] ?? '');
                $precoCompra   = $this->toFloat($row[1] ?? '0');
                $precoRevenda  = $this->toFloat($row[2] ?? '0');
                $pontuacao     = $this->toFloat($row[3] ?? '0');
                $dataInicioRaw = trim($row[4] ?? '');
                $dataFimRaw    = trim($row[5] ?? '');
                $ciclo         = trim($row[6] ?? '');

                // novos campos (se arquivo antigo não tiver, virão nulos)
                $codNotaFiscal = isset($row[7]) ? trim($row[7]) : '';
                $ean           = isset($row[8]) ? trim($row[8]) : '';

                if ($codFabrica === '') {
                    continue;
                }

                $dataInicio = $this->toDate($dataInicioRaw);
                $dataFim    = $this->toDate($dataFimRaw);

            // 1) Localiza produto pelo codfabnumero (Cód. Fábrica)
                /** @var Produto|null $produto */
                $produto = Produto::where('codfabnumero', $codFabrica)->first();

                if (! $produto) {
                    $naoEncontrado++;
                    continue;
                }

                // 2) Atualiza preços, pontuação e NOVOS CAMPOS no produto
                $produto->preco_compra  = $precoCompra;
                $produto->preco_revenda = $precoRevenda;
                $produto->pontuacao     = $pontuacao;

                // só sobrescreve se vier algo no arquivo
                if ($codNotaFiscal !== '') {
                    $produto->codnotafiscal = $codNotaFiscal;
                }
                if ($ean !== '') {
                    $produto->ean = $ean;
                }

                $produto->save();

                // 3) Upsert na tabela de preços
                $dados = [
                    'produto_id'    => $produto->id,
                    'codfab'        => $codFabrica,
                    'preco_compra'  => $precoCompra,
                    'preco_revenda' => $precoRevenda,
                    'pontuacao'     => $pontuacao,
                    'data_inicio'   => $dataInicio,
                    'data_fim'      => $dataFim,
                    'status'        => 1,
                    'ciclo'         => $ciclo !== '' ? $ciclo : null,
                    // 🔹 novos campos na apptabelapreco:
                    'codnotafiscal' => $codNotaFiscal !== '' ? $codNotaFiscal : null,
                    'ean'           => $ean !== '' ? $ean : null,
                ];

                TabelaPreco::updateOrCreate(
                    [
                        'produto_id' => $produto->id,
                        'codfab'     => $codFabrica,
                        'ciclo'      => $dados['ciclo'], // uma linha por ciclo
                    ],
                    $dados
                );

                $atualizados++;
            }

            fclose($handle);
            DB::commit();

            return back()->with(
                'success',
                "Importação concluída. Linhas lidas: {$totalLinhas}, atualizados: {$atualizados}, códigos não encontrados: {$naoEncontrado}."
            );
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }

            return back()->with('error', 'Erro na importação: ' . $e->getMessage());
        }
    }


    /**
     * Converte '10,50' ou '1.234,56' em float 10.50 / 1234.56
     */
    private function toFloat(?string $value): float
    {
        $v = trim($value ?? '0');
        if ($v === '') {
            return 0.0;
        }

        // remove separador de milhar e troca vírgula por ponto
        // "1.234,56" -> "1234.56"
        $v = str_replace(['.', ','], ['', '.'], $v);

        return (float) $v;
    }

    /**
     * Converte '31/10/2025' em '2025-10-31'. Se vier vazio, retorna null.
     */
    private function toDate(?string $value): ?string
    {
        $v = trim($value ?? '');
        if ($v === '') {
            return null;
        }

        // se já estiver YYYY-MM-DD, só devolve
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }

        // espera DD/MM/AAAA
        $parts = explode('/', $v);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        return null;
    }
}
