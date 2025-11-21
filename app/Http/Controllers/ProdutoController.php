<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Fornecedor;
use App\Models\Tabelapreco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = Produto::with(['categoria', 'subcategoria', 'fornecedor']);

        // Filtros
        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', '%' . $busca . '%')
                    ->orWhere('codfab', 'like', '%' . $busca . '%')
                    ->orWhere('codfabnumero', 'like', '%' . $busca . '%');
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Itens por página (somente valores permitidos)
        $allowed = [10, 25, 50, 100];
        $porPagina = (int) $request->get('por_pagina', 10);
        if (! in_array($porPagina, $allowed)) {
            $porPagina = 10;
        }

        $produtos   = $query->orderBy('nome')->paginate($porPagina)->withQueryString();
        $categorias = Categoria::orderBy('nome')->get();

        return view('produtos.index', compact('produtos', 'categorias'));
    }

    private function brToFloat(?string $valor): float
    {
        $valor = trim($valor ?? '');

        if ($valor === '') {
            return 0.0;
        }

        // remove "R$" e espaços
        $valor = str_replace(['R$', ' '], '', $valor);

        // remove separador de milhar
        $valor = str_replace('.', '', $valor);

        // troca vírgula decimal por ponto
        $valor = str_replace(',', '.', $valor);

        return (float) $valor;
    }

    public function create()
    {
        $categorias = Categoria::all();
        $subcategorias = Subcategoria::all();
        $fornecedores = Fornecedor::all();
        return view('produtos.create', compact('categorias', 'subcategorias', 'fornecedores'));
    }

        /**
     * Formulário para importar preços a partir de arquivo do fornecedor.
     * Formato esperado:
     * codigo;preco_compra;preco_revenda;pontuacao
     */
    public function importarPrecosForm()
    {
        return view('produtos.importar_precos');
    }

    /**
     * Processa o arquivo e atualiza appproduto + apptabelapreco.
     */
    public function importarPrecosStore(Request $request)
    {
        $request->validate([
            'arquivo_precos' => 'required|file|mimes:csv,txt,text',
        ]);

        $arquivo  = $request->file('arquivo_precos');
        $conteudo = file_get_contents($arquivo->getRealPath()) ?: '';

        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);
        $hoje   = now()->toDateString();

        $totalLinhas       = 0;
        $totalAtualizados  = 0;
        $totalNaoEncontrados = 0;
        $codigosNaoEncontrados = [];

        DB::beginTransaction();

        try {
            foreach ($linhas as $linha) {
                $linha = trim($linha);

                if ($linha === '') {
                    continue;
                }

                // pula header se tiver
                if (stripos($linha, 'codigo') === 0) {
                    continue;
                }

                // tenta primeiro com ';'
                $cols = str_getcsv($linha, ';');

                // se só veio uma coluna, tenta com ','
                if (count($cols) === 1) {
                    $cols = str_getcsv($linha, ',');
                }

                if (count($cols) < 4) {
                    // formato inválido, ignora linha
                    continue;
                }

                $totalLinhas++;

                $codigo       = trim($cols[0] ?? '');
                $precoCompraB = trim($cols[1] ?? '0');
                $precoVendB   = trim($cols[2] ?? '0');
                $pontosB      = trim($cols[3] ?? '0');

                if ($codigo === '') {
                    continue;
                }

                // Converte BR -> float usando seu helper
                $precoCompra = $this->brToFloat($precoCompraB);
                $precoVenda  = $this->brToFloat($precoVendB);
                $pontos      = $this->brToFloat($pontosB);

                // Procura produto pelo codfabnumero
                /** @var \App\Models\Produto|null $produto */
                $produto = Produto::where('codfabnumero', $codigo)->first();

                if (! $produto) {
                    $totalNaoEncontrados++;
                    $codigosNaoEncontrados[] = $codigo . ' - ' . $precoVendB;
                    continue;
                }

                // Atualiza campos no appproduto
                $produto->preco_compra  = $precoCompra;
                $produto->preco_revenda = $precoVenda;
                $produto->pontos        = $pontos;
                $produto->save();

                // Fecha tabela de preço vigente e cria nova
                // Ajuste os nomes dos campos conforme seu migration/model de Tabelapreco
                Tabelapreco::where('produto_id', $produto->id)
                    ->where('status', 1)
                    ->update([
                        'status'    => 0,
                        'data_fim'  => $hoje,
                    ]);

                Tabelapreco::create([
                    'produto_id'    => $produto->id,
                    'preco_compra'  => $precoCompra,
                    'preco_revenda' => $precoVenda,
                    'pontuacao'     => $pontos,
                    'data_inicio'   => $hoje,
                    'data_fim'      => null,
                    'status'        => 1,
                ]);

                $totalAtualizados++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withErrors(['arquivo_precos' => 'Erro ao importar preços: ' . $e->getMessage()])
                ->withInput();
        }

        // Se tiver códigos não encontrados, gera um TXT pra você poder tratar depois
        if (!empty($codigosNaoEncontrados)) {
            $nomeArquivo = 'precos_nao_encontrados_' . date('Ymd_His') . '.txt';
            $caminho     = storage_path('app/' . $nomeArquivo);

            $linhasTxt   = [];
            $linhasTxt[] = 'Itens com código não encontrado na base (appproduto)';
            $linhasTxt[] = 'Formato: codigo - preco_revenda_informado';
            $linhasTxt[] = str_repeat('-', 60);

            foreach ($codigosNaoEncontrados as $linhaCod) {
                $linhasTxt[] = $linhaCod;
            }

            file_put_contents($caminho, implode(PHP_EOL, $linhasTxt));

            // Podemos mandar um link pra download (via Storage::download)
            session()->flash('arquivo_nao_encontrados', $nomeArquivo);
        }

        $msg = "Importação concluída. Linhas lidas: {$totalLinhas}. "
             . "Produtos atualizados: {$totalAtualizados}. "
             . "Códigos não encontrados: {$totalNaoEncontrados}.";

        return redirect()
            ->route('produtos.importar_precos.form')
            ->with('success', $msg);
    }

    /**
     * Formulário para importar TXT de itens não importados (WhatsApp).
     */
    public function importarMissingForm(Request $request)
    {
        $itens = [];

        if ($request->hasFile('arquivo')) {
            $arquivo = $request->file('arquivo');
            $linhas = file($arquivo->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($linhas as $linha) {
                // Ex: CODIGO;DESCRICAO;PRECO_REVENDA
                $cols = explode(';', $linha);

                $codigo   = trim($cols[0] ?? '');
                $desc     = trim($cols[1] ?? '');
                $precoBr  = trim($cols[2] ?? '0');

                $itens[] = [
                    'codigo_fabrica' => $codigo,
                    'descricao'      => $desc,
                    // 👇 agora converte corretamente "112,25" em 112.25
                    'preco_revenda'  => $this->brToFloat($precoBr),
                ];
            }
        }

        // fornecedor/categoria/sub etc que você já está passando
        $fornecedores  = \App\Models\Fornecedor::orderBy('nomefantasia')->get();
        $categorias    = \App\Models\Categoria::orderBy('nome')->get();
        $subcategorias = \App\Models\Subcategoria::orderBy('nome')->get();

        return view('produtos.importar_missing', compact('itens', 'fornecedores', 'categorias', 'subcategorias'));
    }


    /**
     * Processa o TXT e/ou grava os produtos.
     */
    public function importarMissingStore(Request $request)
    {
        // PASSO 2: SALVAR PRODUTOS
        if ($request->has('salvar')) {
            $dados = $request->input('itens', []);
            $criados   = 0;
            $ignorados = 0;

            DB::beginTransaction();

            try {
                foreach ($dados as $item) {
                    if (empty($item['importar'])) {
                        $ignorados++;
                        continue;
                    }

                    $codigo    = trim($item['codfabnumero'] ?? '');
                    $descricao = trim($item['nome'] ?? '');

                    $precoCompra   = $this->brToFloat($item['preco_compra']   ?? '0');
                    $precoRevenda  = $this->brToFloat($item['preco_revenda']  ?? '0');
                    if ($precoCompra <= 0 && $precoRevenda > 0) {
                        $precoCompra = round($precoRevenda * 0.7, 2);
                    }
                    // Pontuação
                    $pontuacaoStr  = (string) ($item['pontuacao'] ?? '0');
                    $pontuacaoStr  = str_replace('.', '', $pontuacaoStr);
                    $pontuacaoStr  = str_replace(',', '.', $pontuacaoStr);
                    $pontuacao     = (float) $pontuacaoStr;

                    $fornecedorId   = (int) ($item['fornecedor_id'] ?? 0);
                    $categoriaId    = (int) ($item['categoria_id'] ?? 0);
                    $subcategoriaId = (int) ($item['subcategoria_id'] ?? 0);

                    if ($codigo === '' || $descricao === '') {
                        $ignorados++;
                        continue;
                    }

                    if (Produto::where('codfabnumero', $codigo)->exists()) {
                        $ignorados++;
                        continue;
                    }

                    // 1) Cria o produto
                    $produto = Produto::create([
                        'codfabnumero'    => $codigo,
                        'nome'            => $descricao,
                        'preco_compra'    => $precoCompra,
                        'preco_revenda'   => $precoRevenda,
                        'pontos'          => $pontuacao, // campo da tabela appproduto
                        'fornecedor_id'   => $fornecedorId ?: null,
                        'categoria_id'    => $categoriaId ?: null,
                        'subcategoria_id' => $subcategoriaId ?: null,
                    ]);

                    // 2) Cria registro de tabela de preço vigente
                    Tabelapreco::create([
                        'produto_id'   => $produto->id,
                        'preco_compra' => $precoCompra,
                        'preco_revenda' => $precoRevenda,
                        'pontuacao'    => $pontuacao,   // campo da tabela apptabelapreco
                        'status'       => 1,
                        'data_inicio'  => now()->toDateString(),
                    ]);

                    $criados++;
                }

                DB::commit();

                return redirect()
                    ->route('produtos.index')
                    ->with('success', "Importação concluída. Produtos criados: {$criados}. Ignorados/duplicados: {$ignorados}.");
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()
                    ->withErrors(['import' => 'Erro ao salvar produtos: ' . $e->getMessage()])
                    ->withInput();
            }
        }

        // PASSO 1: LER TXT E MONTAR TABELA
        $request->validate([
            'arquivo_txt' => 'required|file|mimes:txt,text',
        ]);

        $arquivo  = $request->file('arquivo_txt');
        $conteudo = file_get_contents($arquivo->getRealPath()) ?: '';

        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);
        $itens  = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, 'Itens não importados')) {
                continue;
            }

            // Formato: Código: 6587 | Qtde: 1 | Preço: 25.90 | Descrição: ...
            if (!preg_match(
                '/^Código:\s*(.+?)\s*\|\s*Qtde:\s*([\d.,]+)\s*\|\s*Preço:\s*([\d.,]*)\s*\|\s*Descrição:\s*(.*)$/u',
                $linha,
                $m
            )) {
                continue;
            }

            $codigo    = trim($m[1] ?? '');
            $descricao = trim($m[4] ?? '');
            $precoStr  = trim($m[3] ?? '');

            // Nosso TXT gerado no front usa ponto como decimal (toFixed(2)), mas
            // vamos tratar vírgula também por segurança.
            if ($precoStr !== '') {
                // TXT gerado pelo sistema usa ponto como decimal (ex: 112.25).
                // Se vier com vírgula por algum motivo, só trocamos por ponto.
                $precoStr = str_replace('R$', '', $precoStr);
                $precoStr = str_replace(' ', '', $precoStr);
                $precoStr = str_replace(',', '.', $precoStr);

                // NÃO removemos o ponto, porque aqui ele é decimal e não milhar
                $precoRevenda = (float) $precoStr;
            } else {
                $precoRevenda = 0.0;
            }


            if ($codigo === '') {
                continue;
            }

            $itens[] = [
                'codfabnumero'  => $codigo,
                'nome'          => $descricao,
                'preco_revenda' => $precoRevenda,
            ];
        }

        if (empty($itens)) {
            return back()
                ->withErrors(['arquivo_txt' => 'Nenhum item válido foi encontrado no arquivo.'])
                ->withInput();
        }

        // Carrega listas pra sugerir fornecedor/categoria/subcategoria
        $fornecedores   = Fornecedor::orderBy('nomefantasia')->get();
        $categorias     = Categoria::orderBy('nome')->get();
        $subcategorias  = Subcategoria::orderBy('nome')->get();

        $fornecedorPadraoId   = optional($fornecedores->first())->id;
        $categoriaPadraoId    = optional($categorias->first())->id;
        $subcategoriaPadraoId = optional($subcategorias->first())->id;

        return view('produtos.importar_missing', [
            'itens'                => $itens,
            'fornecedores'         => $fornecedores,
            'categorias'           => $categorias,
            'subcategorias'        => $subcategorias,
            'fornecedorPadraoId'   => $fornecedorPadraoId,
            'categoriaPadraoId'    => $categoriaPadraoId,
            'subcategoriaPadraoId' => $subcategoriaPadraoId,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'fornecedor_id' => 'required',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $dados = $request->all();

        if ($request->hasFile('imagem')) {
            $nomeArquivo = time() . '.' . $request->imagem->extension();
            $request->imagem->move(public_path('imagens/produtos'), $nomeArquivo);
            $dados['imagem'] = 'imagens/produtos/' . $nomeArquivo;
        }

        Produto::create($dados);

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    /**
     * Retorna preço e pontuação pelo codfabnumero
     */
    public function getPreco($codfabnumero)
    {
        $hoje = now()->toDateString();

        $preco = DB::table('apptabelapreco')
            ->join('appproduto', 'apptabelapreco.produto_id', '=', 'appproduto.id')
            ->where('appproduto.codfabnumero', $codfabnumero)
            ->where('apptabelapreco.status', 1)
            ->whereDate('apptabelapreco.data_inicio', '<=', $hoje)
            ->whereDate('apptabelapreco.data_fim', '>=', $hoje)
            ->orderByDesc('apptabelapreco.data_inicio')
            ->select('apptabelapreco.preco_revenda', 'apptabelapreco.pontuacao')
            ->first();

        if ($preco) {
            return response()->json([
                'preco_revenda' => (float) $preco->preco_revenda,
                'pontuacao' => (float) $preco->pontuacao,
            ]);
        }

        return response()->json(['preco_revenda' => 0, 'pontuacao' => 0]);
    }

    public function edit(Produto $produto)
    {
        $categorias = Categoria::all();
        $subcategorias = Subcategoria::all();
        $fornecedores = Fornecedor::all();
        return view('produtos.edit', compact('produto', 'categorias', 'subcategorias', 'fornecedores'));
    }

    public function update(Request $request, Produto $produto)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'fornecedor_id' => 'required',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $dados = $request->all();

        if ($request->hasFile('imagem')) {
            if ($produto->imagem && file_exists(public_path($produto->imagem))) {
                unlink(public_path($produto->imagem));
            }

            $nomeArquivo = time() . '.' . $request->imagem->extension();
            $request->imagem->move(public_path('imagens/produtos'), $nomeArquivo);
            $dados['imagem'] = 'imagens/produtos/' . $nomeArquivo;
        }

        $produto->update($dados);

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();
        return redirect()->route('produtos.index')->with('success', 'Produto excluído com sucesso!');
    }
}
