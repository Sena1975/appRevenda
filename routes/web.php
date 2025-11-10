<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProdutoController;
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
use App\Http\Controllers\PlanoPagamentoController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\CampanhaProdutoController;
use App\Http\Controllers\AniversarianteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProdutoLookupController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {

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
    */
    Route::resource('produtos', ProdutoController::class);
    Route::resource('fornecedores', AppFornecedorController::class);
    Route::resource('categorias', CategoriaController::class);
    Route::resource('subcategorias', SubcategoriaController::class);
    Route::resource('revendedoras', RevendedoraController::class);
    Route::resource('equiperevenda', \App\Http\Controllers\EquipeRevendaController::class);
    Route::resource('supervisores', SupervisorController::class);
    Route::resource('clientes', ClienteController::class);
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
    | ROTA PARA VIEW DE PRODUTOS COM ESTOQUE E PREÇO
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        Route::get('/produtos/buscar', [ProdutoLookupController::class, 'buscar'])
            ->name('api.produtos.buscar');
    });

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
