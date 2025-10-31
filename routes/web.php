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
use app\Http\Controllers\BaixaReceberController;
use App\Http\Controllers\PlanoPagamentoController;
use App\Http\Controllers\FormaPagamentoController;

use App\Models\PlanoPagamento;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aqui ficam todas as rotas da aplicação web protegidas por autenticação.
| Todas as funcionalidades do painel (CRUDs, relatórios e consultas)
| estão organizadas dentro do grupo middleware(['auth']).
|
*/

// Página inicial (welcome)

Route::get('/', function () {
    return view('welcome');
});

// Painel principal
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Agrupamento de rotas protegidas por autenticação
Route::middleware(['auth'])->group(function () {
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
    Route::resource('equiperevenda', EquipeRevendaController::class);
    Route::resource('supervisores', SupervisorController::class);
    Route::resource('clientes', ClienteController::class);
    Route::resource('tabelapreco', TabelaprecoController::class);
    /*
    |--------------------------------------------------------------------------
    | LOCALIZAÇÃO (UF, CIDADES, BAIRROS)
    |--------------------------------------------------------------------------
    */
 
    Route::get('/get-cidades/{uf_id}', [LocalizacaoController::class, 'getCidades'])->name('get.cidades');
    Route::get('/get-bairros/{cidade_id}', [LocalizacaoController::class, 'getBairros'])->name('get.bairros');
    Route::get('/get-localizacao', [LocalizacaoController::class, 'getLocalizacao'])->name('get.localizacao');

    /*
    |--------------------------------------------------------------------------
    | COMPRAS E ESTOQUE
    |--------------------------------------------------------------------------
    */    
    Route::get('/compras/{id}/importar', [PedidoCompraController::class, 'importarItens'])->name('compras.importar');
    Route::post('/compras/{id}/importar', [PedidoCompraController::class, 'processarImportacao'])->name('compras.processarImportacao');
    Route::get('/compras/{id}/exportar', [PedidoCompraController::class, 'exportarItens'])->name('compras.exportar');
    Route::resource('compras', PedidoCompraController::class);

    Route::resource('estoque', EstoqueController::class);
    Route::resource('movestoque', MovEstoqueController::class);

    /*
    |--------------------------------------------------------------------------
    | VENDAS E FINANCEIRO
    |--------------------------------------------------------------------------
    */
    // Vendas
    Route::resource('vendas', \App\Http\Controllers\PedidoVendaController::class);
 // Exportar um pedido de venda (CSV)
    Route::get('vendas/{id}/exportar', [\App\Http\Controllers\PedidoVendaController::class, 'exportar'])
        ->name('vendas.exportar');
    Route::get('vendas/confirmar/{id}', [PedidoVendaController::class, 'confirmar'])->name('vendas.confirmar');    

    // Route::put('vendas/{id}/status', [PedidoVendaController::class, 'updateStatus'])->name('vendas.updateStatus');

    // Route::resource('vendas', PedidoVendaController::class);
    // Route::get('/vendas/novo', [PedidoVendaController::class, 'create'])->name('vendas.create');
    // Route::post('/vendas', [PedidoVendaController::class, 'store'])->name('vendas.store');

    // Contas a receber e baixas
    Route::get('financeiro/contas', [ContasReceberController::class, 'index'])->name('contas.index');
    Route::get('financeiro/contas/{id}', [ContasReceberController::class, 'show'])->name('contas.show');
    Route::get('financeiro/contas/{id}/baixa', [BaixaReceberController::class, 'create'])->name('baixa.create');
    Route::post('financeiro/contas/{id}/baixa', [BaixaReceberController::class, 'store'])->name('baixa.store');

    /*
    |--------------------------------------------------------------------------
    | FORMAS E PLANOS DE PAGAMENTO
    |--------------------------------------------------------------------------
    */   

    Route::resource('planopagamento', PlanoPagamentoController::class);   
    Route::resource('formapagamento', FormaPagamentoController::class);

    // Ajax: buscar planos de pagamento por forma
    Route::get('/planos-por-forma/{forma_id}', [PlanoPagamentoController::class, 'getByForma'])
        ->name('planopagamento.getByForma');


    // VERIFICAR PLANOS     
    Route::get('planos/byforma/{id}', [PlanoPagamentoController::class, 'getByForma']);    
    Route::get('planos/by-forma/{formaId}', function($formaId) {return PlanoPagamento::where('forma_pagamento_id', $formaId)->get();});



    Route::resource('contasreceber', ContasReceberController::class);

  

    // // Opcional: endpoint para buscar detalhes de produto pelo código (se precisar)
    Route::get('/produto/bycod/{codfabnumero}', function ($codfabnumero) {
    $produto = DB::table('appproduto')
        ->join('apptabelapreco', 'appproduto.id', '=', 'apptabelapreco.produto_id')
        ->where('appproduto.codfabnumero', $codfabnumero)
        ->select(
            'appproduto.id',
            'appproduto.codfabnumero',
            'appproduto.nome',
            'apptabelapreco.preco_compra',
            'apptabelapreco.preco_revenda as preco_venda',
            'apptabelapreco.pontuacao'
        )
        ->first();

    return response()->json($produto ?? []);
    });
    /*
    |--------------------------------------------------------------------------
    | PERFIL DO USUÁRIO
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


});

require __DIR__.'/auth.php';
