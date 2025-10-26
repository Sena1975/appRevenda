<?php

use Illuminate\Support\Facades\Route;
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


Route::get('/', function () {
    return view('welcome');
});

// Rota do painel principal (Dashboard)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Agrupamento de rotas protegidas por autenticação
Route::middleware(['auth'])->group(function () {
    Route::resource('produtos', ProdutoController::class);
    Route::resource('fornecedores', AppFornecedorController::class);
    Route::resource('categorias', CategoriaController::class);
    Route::resource('subcategorias', SubcategoriaController::class);
    Route::resource('revendedoras', RevendedoraController::class);
    Route::resource('equiperevenda', EquipeRevendaController::class);
    Route::resource('supervisores', SupervisorController::class);
    Route::resource('clientes', ClienteController::class);
    // Rotas AJAX para os combos dinâmicos
    Route::get('/get-cidades/{uf_id}', [LocalizacaoController::class, 'getCidades'])->name('get.cidades');
    Route::get('/get-bairros/{cidade_id}', [LocalizacaoController::class, 'getBairros'])->name('get.bairros');
    Route::get('/get-localizacao', [LocalizacaoController::class, 'getLocalizacao'])->name('get.localizacao');


    // Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
