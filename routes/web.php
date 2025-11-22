<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProdutoLookupController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SubcategoriaController;
use App\Http\Controllers\RevendedoraController;
use App\Http\Controllers\EquipeRevendaController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LocalizacaoController;
use App\Http\Controllers\AppFornecedorController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\MovEstoqueController;
use App\Http\Controllers\TabelaprecoController;
use App\Http\Controllers\PedidoCompraController;
use App\Http\Controllers\PedidoVendaController;
use App\Http\Controllers\ContasReceberController;
use App\Http\Controllers\ContasPagarController;
use App\Http\Controllers\PlanoPagamentoController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\CampanhaProdutoController;
use App\Http\Controllers\AniversarianteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RelatorioFinanceiroController;
use App\Http\Controllers\TextoPedidoController;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
// DEBUG: descobrir de onde está rodando o app
Route::get('/whoami', function () {
    return base_path();
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cadastro-cliente', [ClienteController::class, 'createPublic'])
    ->name('clientes.public.create');

Route::post('/cadastro-cliente', [ClienteController::class, 'storePublic'])
    ->name('clientes.public.store');



Route::middleware(['auth'])->group(function () {

    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        // (já existiam)
        Route::get('/recebimentos/previsao', [RelatorioFinanceiroController::class, 'previsaoRecebimentos'])
            ->name('recebimentos.previsao');

        Route::get('/pagamentos/previsao', [RelatorioFinanceiroController::class, 'previsaoPagamentos'])
            ->name('pagamentos.previsao');

        Route::get('/recebimentos/inadimplencia', [RelatorioFinanceiroController::class, 'inadimplenciaReceber'])
            ->name('recebimentos.inadimplencia');

        // ✅ Extrato Financeiro do Cliente (já criamos antes)
        Route::get('/recebimentos/extrato-cliente', [RelatorioFinanceiroController::class, 'extratoCliente'])
            ->name('recebimentos.extrato_cliente');

        // ✅ Novos (podem ser stubs por enquanto)
        Route::get('/clientes/{cliente}/extrato-pedidos', [RelatorioFinanceiroController::class, 'extratoPedidosCliente'])
            ->name('clientes.extrato_pedidos');

        Route::get('/clientes/{cliente}/extrato-produtos', [RelatorioFinanceiroController::class, 'extratoProdutosCliente'])
            ->name('clientes.extrato_produtos');
    });
    Route::get('/clientes/qrcode', function () {
        return view('clientes.qrcode');
    })->name('clientes.qrcode');
    Route::get('/clientes/qrcode.png', function () {
        $png = QrCode::format('png')
            ->size(600)
            ->margin(2)
            ->generate(route('clientes.public.create'));

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="qrcode-cadastro-cliente.png"');
    })->name('clientes.qrcode.png');
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | CADASTROS BÁSICOS
    |--------------------------------------------------------------------------
    | ROTA PARA VIEW DE PRODUTOS COM ESTOQUE E PREÇO
    |--------------------------------------------------------------------------
    */
    // 1) LOOKUP PRIMEIRO
    Route::get('/produtos/lookup', [ProdutoLookupController::class, 'buscar'])
        ->name('produtos.lookup');

    Route::prefix('api')->group(function () {
        Route::get('/produtos/buscar', [ProdutoLookupController::class, 'buscar'])
            ->name('api.produtos.buscar');
    });

    // Importar produtos a partir do TXT de itens não importados
    Route::get('/produtos/importar-missing', [ProdutoController::class, 'importarMissingForm'])
        ->name('produtos.importar_missing.form');

    Route::post('/produtos/importar-missing', [ProdutoController::class, 'importarMissingStore'])
        ->name('produtos.importar_missing.store');
    // 2) RESOURCE SEM SHOW (você não tem método show no controller)
    Route::resource('produtos', ProdutoController::class)->except(['show']);

    // restante você deixa igual:
    Route::resource('fornecedores', AppFornecedorController::class);
    Route::resource('categorias', CategoriaController::class);
    Route::resource('subcategorias', SubcategoriaController::class);
    Route::resource('revendedoras', RevendedoraController::class);
    Route::resource('equiperevenda', \App\Http\Controllers\EquipeRevendaController::class);
    Route::resource('supervisores', SupervisorController::class);
    Route::resource('clientes', ClienteController::class);

    Route::get('tabelapreco/importar', [TabelaPrecoController::class, 'formImport'])
        ->name('tabelapreco.formImport');

    Route::post('tabelapreco/importar', [TabelaPrecoController::class, 'processImport'])
        ->name('tabelapreco.processImport');

    Route::resource('tabelapreco', TabelaprecoController::class);

    /*
    |--------------------------------------------------------------------------
    | LOCALIZAÇÃO (UF, CIDADES, BAIRROS)
    |--------------------------------------------------------------------------
    | Mantemos APENAS as rotas do controller para evitar duplicidade de URIs.
    */
    Route::get('/get-cidades/{uf_id}',   [LocalizacaoController::class, 'getCidades'])->name('get.cidades');
    Route::get('/get-bairros/{cidade_id}', [LocalizacaoController::class, 'getBairros'])->name('get.bairros');
    Route::get('/get-localizacao',       [LocalizacaoController::class, 'getLocalizacao'])->name('get.localizacao');

    /*
    |--------------------------------------------------------------------------
    | COMPRAS E ESTOQUE
    |--------------------------------------------------------------------------
    */
    Route::get('/compras/{id}/importar',  [PedidoCompraController::class, 'importarItens'])->name('compras.importar');
    Route::post('/compras/{id}/importar', [PedidoCompraController::class, 'processarImportacao'])->name('compras.processarImportacao');
    Route::get('/compras/{id}/exportar',  [PedidoCompraController::class, 'exportarItens'])->name('compras.exportar');
    Route::resource('compras', PedidoCompraController::class);

    Route::resource('estoque', EstoqueController::class);
    Route::resource('movestoque', MovEstoqueController::class);
    /*
    |--------------------------------------------------------------------------
    | VENDAS
    |--------------------------------------------------------------------------
    */
    Route::resource('vendas', PedidoVendaController::class);
    Route::get('vendas/{id}/exportar', [PedidoVendaController::class, 'exportar'])->name('vendas.exportar');
    Route::get('vendas/confirmar/{id}', [PedidoVendaController::class, 'confirmar'])->name('vendas.confirmar');
    Route::post('/vendas/{id}/cancelar', [PedidoVendaController::class, 'cancelar'])
        ->name('vendas.cancelar');
    Route::post('/vendas/{id}/confirmar-entrega', [PedidoVendaController::class, 'confirmarEntrega'])
        ->name('vendas.confirmarEntrega');

    /*
    |--------------------------------------------------------------------------
    | Ferramenta: importar pedido via texto (WhatsApp)
    |--------------------------------------------------------------------------
    */
    Route::post('/vendas/importar-texto-whatsapp', [PedidoVendaController::class, 'importarTextoWhatsapp'])
        ->name('vendas.importar.texto');

    Route::get('/tools/importar-pedido-texto', [TextoPedidoController::class, 'form'])
        ->name('tools.importar_pedido_texto');

    Route::post('/tools/importar-pedido-texto', [TextoPedidoController::class, 'gerarCsv'])
        ->name('tools.importar_pedido_texto.post');

    Route::get('produtos/nao-encontrados/{arquivo}', function ($arquivo) {
        $path = $arquivo;

        if (!Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    })->name('produtos.download_nao_encontrados');
    /*
    |--------------------------------------------------------------------------
    | FINANCEIRO – CONTAS A RECEBER
    |--------------------------------------------------------------------------
    | Alinhado ao ContasReceberController (métodos: baixa [GET], baixar [POST], recibo).
    | Mantemos um alias baixaForm() no controller para compatibilidade, se algum
    | link antigo chamar.
    */

    Route::resource('contasreceber', \App\Http\Controllers\ContasReceberController::class);

    // Baixa (GET formulário / POST efetiva)
    Route::get('contasreceber/{id}/baixa',  [\App\Http\Controllers\ContasReceberController::class, 'baixar'])
        ->name('contasreceber.baixa');
    Route::post('contasreceber/{id}/baixa', [\App\Http\Controllers\ContasReceberController::class, 'baixarStore'])
        ->name('contasreceber.baixa.store');
    // Estorno + Recibo
    Route::post('contasreceber/{id}/estornar', [\App\Http\Controllers\ContasReceberController::class, 'estornar'])
        ->name('contasreceber.estornar');
    Route::get('contasreceber/{id}/recibo',   [\App\Http\Controllers\ContasReceberController::class, 'recibo'])
        ->name('contasreceber.recibo');

    /*
    |--------------------------------------------------------------------------
    | FINANCEIRO – CONTAS A PAGAR
    |--------------------------------------------------------------------------
    */

    // Listar + editar dados da conta
    Route::resource('contaspagar', ContasPagarController::class)
        ->only(['index', 'edit', 'update']);

    // Tela de baixa (GET) + ação de baixa (POST)
    Route::get('contaspagar/{conta}/baixar', [ContasPagarController::class, 'formBaixa'])
        ->name('contaspagar.formBaixa');

    Route::post('contaspagar/{conta}/baixar', [ContasPagarController::class, 'baixar'])
        ->name('contaspagar.baixar');

    Route::post('contaspagar/{conta}/estornar', [ContasPagarController::class, 'estornar'])
        ->name('contaspagar.estornar');
    /*
    |--------------------------------------------------------------------------
    | PRODUTO: buscar por CODFAB (preço/pontos da tabela vigente)
    |--------------------------------------------------------------------------
    */
    Route::get('/produto/bycod/{codfabnumero}', function ($codfabnumero) {
        $hoje = now()->toDateString();

        $produto = DB::table('appproduto as p')
            ->leftJoin('apptabelapreco as t', function ($join) use ($hoje) {
                $join->on('t.produto_id', '=', 'p.id')
                    ->where('t.status', 1)
                    ->whereDate('t.data_inicio', '<=', $hoje)
                    ->where(function ($q) use ($hoje) {
                        $q->whereNull('t.data_fim')
                            ->orWhereDate('t.data_fim', '>=', $hoje);
                    });
            })
            ->where('p.codfabnumero', $codfabnumero)
            ->select(
                'p.id',
                'p.codfabnumero',
                'p.nome',
                DB::raw('COALESCE(t.preco_compra, 0) as preco_compra'),
                DB::raw('COALESCE(t.preco_revenda, 0) as preco_venda'),
                DB::raw('COALESCE(t.pontuacao, 0) as pontuacao')
            )
            ->first();

        return response()->json($produto ?? []);
    });
    // IMPORTAÇÃO DE PREÇOS (arquivo do fornecedor)
    Route::get('produtos/importar-precos', [ProdutoController::class, 'importarPrecosForm'])
        ->name('produtos.importar_precos.form');

    Route::post('produtos/importar-precos', [ProdutoController::class, 'importarPrecosStore'])
        ->name('produtos.importar_precos.store');
    /*
    |--------------------------------------------------------------------------
    | FORMAS E PLANOS DE PAGAMENTO
    |--------------------------------------------------------------------------
    */
    Route::resource('planopagamento', PlanoPagamentoController::class);
    Route::resource('formapagamento', FormaPagamentoController::class);

    // Ajax oficial usado no front (create/edit de vendas)
    Route::get('/planos-por-forma/{forma_id}', [PlanoPagamentoController::class, 'getByForma'])
        ->name('planopagamento.getByForma');

    /*
    |--------------------------------------------------------------------------
    | PERFIL DO USUÁRIO
    |--------------------------------------------------------------------------
    */
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | API simples para o painel
    |--------------------------------------------------------------------------
    */
    Route::get('/aniversariantes/{mes}/json', [AniversarianteController::class, 'listarJson'])
        ->whereNumber('mes')
        ->name('aniversariantes.json');

    /*
    |--------------------------------------------------------------------------
    | CAMPANHAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('campanhas')->group(function () {
        Route::get('/',       [CampanhaController::class, 'index'])->name('campanhas.index');
        Route::get('/create', [CampanhaController::class, 'create'])->name('campanhas.create');
        Route::post('/',      [CampanhaController::class, 'store'])->name('campanhas.store');

        // Restrições
        Route::get('{campanha}/restricoes',         [CampanhaProdutoController::class, 'index'])->name('campanhas.restricoes');
        Route::post('{campanha}/restricoes',        [CampanhaProdutoController::class, 'store'])->name('campanhas.restricoes.store');
        Route::delete('{campanha}/restricoes/{id}', [CampanhaProdutoController::class, 'destroy'])->name('campanhas.restricoes.destroy');
    });
});

require __DIR__ . '/auth.php';
