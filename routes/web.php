<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
use App\Http\Controllers\IndicacaoController;
use App\Http\Controllers\RelatorioMensagensController;
use App\Http\Controllers\MensagemController;
use App\Http\Controllers\MensagensManuaisController;
use App\Http\Controllers\WhatsappConfigController;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

// DEBUG: descobrir de onde está rodando o app
Route::get('/whoami', function () {
    return base_path();
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/cadastro-cliente', [ClienteController::class, 'createPublic'])
    ->name('clientes.public.create');

Route::post('/cadastro-cliente', [ClienteController::class, 'storePublic'])
    ->name('clientes.public.store');


// 👇 aqui entra também o middleware da empresa
Route::middleware(['auth', 'empresa.ativa'])->group(function () {

    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/recebimentos/previsao', [RelatorioFinanceiroController::class, 'previsaoRecebimentos'])->name('recebimentos.previsao');
        Route::get('/pagamentos/previsao', [RelatorioFinanceiroController::class, 'previsaoPagamentos'])->name('pagamentos.previsao');
        Route::get('/recebimentos/inadimplencia', [RelatorioFinanceiroController::class, 'inadimplenciaReceber'])->name('recebimentos.inadimplencia');
        // ✅ Extrato Financeiro do Cliente (já criamos antes)
        Route::get('/recebimentos/extrato-cliente', [RelatorioFinanceiroController::class, 'extratoCliente'])->name('recebimentos.extrato_cliente');
        // ✅ Novos (podem ser stubs por enquanto)
        Route::get('/clientes/{cliente}/extrato-pedidos', [RelatorioFinanceiroController::class, 'extratoPedidosCliente'])->name('clientes.extrato_pedidos');
        Route::get('/clientes/{cliente}/extrato-produtos', [RelatorioFinanceiroController::class, 'extratoProdutosCliente'])->name('clientes.extrato_produtos');
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

    Route::get('/clientes/{cliente}/indicador-info', [ClienteController::class, 'indicadorInfo'])
        ->name('clientes.indicador.info');

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // 1) LOOKUP PRIMEIRO
    Route::get('/produtos/lookup', [ProdutoLookupController::class, 'buscar'])
        ->name('produtos.lookup');

    Route::prefix('api')->group(function () {
        Route::get('/produtos/buscar', [ProdutoLookupController::class, 'buscar'])->name('api.produtos.buscar');
    });

    // Importar produtos a partir do TXT de itens não importados
    Route::get('/produtos/importar-missing', [ProdutoController::class, 'importarMissingForm'])->name('produtos.importar_missing.form');

    Route::post('/produtos/importar-missing', [ProdutoController::class, 'importarMissingStore'])->name('produtos.importar_missing.store');

    // 2) RESOURCE SEM SHOW (você não tem método show no controller)
    Route::resource('produtos', ProdutoController::class)->except(['show']);

    // restante você deixa igual:
    Route::resource('fornecedores', AppFornecedorController::class);
    Route::resource('categorias', CategoriaController::class);
    Route::resource('subcategorias', SubcategoriaController::class);
    Route::resource('revendedoras', RevendedoraController::class);
    Route::resource('equiperevenda', EquipeRevendaController::class);
    Route::resource('supervisores', SupervisorController::class);

    // 👇 Painel de configurações de WhatsApp por empresa
    Route::resource('whatsapp-config', WhatsappConfigController::class)->parameters(['whatsapp-config' => 'whatsappConfig']);
    Route::get('/clientes/mesclar', [ClienteController::class, 'mergeForm'])->name('clientes.merge.form');
    Route::post('/clientes/mesclar', [ClienteController::class, 'mergeStore'])->name('clientes.merge.store');
    Route::resource('clientes', ClienteController::class);
    Route::get('tabelapreco/importar', [TabelaPrecoController::class, 'formImport'])->name('tabelapreco.formImport');
    Route::post('tabelapreco/importar', [TabelaPrecoController::class, 'processImport'])->name('tabelapreco.processImport');
    Route::resource('tabelapreco', TabelaprecoController::class);

    /*
    |--------------------------------------------------------------------------
    | LOCALIZAÇÃO (UF, CIDADES, BAIRROS)
    |--------------------------------------------------------------------------
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
    Route::post('/vendas/{id}/cancelar', [PedidoVendaController::class, 'cancelar'])
        ->name('vendas.cancelar');
    Route::post('/vendas/{id}/confirmar-entrega', [PedidoVendaController::class, 'confirmarEntrega'])
        ->name('vendas.confirmarEntrega');

    /*
    |--------------------------------------------------------------------------
    | CAMPANHA – INDICAÇÕES
    |--------------------------------------------------------------------------
    */
    Route::get('/indicacoes', [IndicacaoController::class, 'index'])->name('indicacoes.index');
    Route::post('/indicacoes/{id}/pagar', [IndicacaoController::class, 'pagar'])->name('indicacoes.pagar');

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
    */
    Route::resource('contasreceber', ContasReceberController::class);

    // Baixa (GET formulário / POST efetiva)
    Route::get('contasreceber/{id}/baixa',  [ContasReceberController::class, 'baixar'])
        ->name('contasreceber.baixa');
    Route::post('contasreceber/{id}/baixa', [ContasReceberController::class, 'baixarStore'])
        ->name('contasreceber.baixa.store');

    // Estorno + Recibo
    Route::post('contasreceber/{id}/estornar', [ContasReceberController::class, 'estornar'])
        ->name('contasreceber.estornar');
    Route::get('contasreceber/{id}/recibo',   [ContasReceberController::class, 'recibo'])
        ->name('contasreceber.recibo');

    /*
    |--------------------------------------------------------------------------
    | FINANCEIRO – CONTAS A PAGAR
    |--------------------------------------------------------------------------
    */
    Route::resource('contaspagar', ContasPagarController::class)
        ->only(['index', 'edit', 'update']);

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
        /** @var \App\Models\Usuario|null $user */
        $user = Auth::user();
        $empresaId = $user?->empresa_id;

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
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->where('p.empresa_id', $empresaId);
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

    // teste WhatsApp (ainda usando BotConversaService direto)
    Route::get('/teste-whatsapp', function (\App\Services\Whatsapp\BotConversaService $whatsapp) {
        $telefone = '557196720776';
        $mensagem = "Teste de BotConversa via appRevenda\nPedido #123\nValor: R$ 50,00";

        $ok = $whatsapp->enviarParaTelefone($telefone, $mensagem, 'Carlos Sena');

        dd(['enviado' => $ok]);
    });

    Route::get('/mensagens', [MensagemController::class, 'index'])
        ->name('mensagens.index');

    // lista (index) - você já deve ter algo assim:
    Route::get('/mensagens', [MensagemController::class, 'index'])
        ->name('mensagens.index');

    // show (detalhes)
    Route::get('/mensagens/{mensagem}', [MensagemController::class, 'show'])
        ->name('mensagens.show');

    Route::get('/relatorios/mensagens/campanhas', [RelatorioMensagensController::class, 'porCampanha'])
        ->name('relatorios.mensagens.por_campanha');

    Route::get('/relatorios/mensagens/clientes', [RelatorioMensagensController::class, 'porCliente'])
        ->name('relatorios.mensagens.por_cliente');

    Route::get('/relatorios/campanhas/indicacao', [RelatorioMensagensController::class, 'campanhasIndicacao'])
        ->name('relatorios.campanhas.indicacao');

    /* envio manual*/
    Route::prefix('mensageria')
        ->name('mensageria.')->group(function () {
            Route::get('modelos', [MensagensManuaisController::class, 'index'])
                ->name('modelos.index');

            Route::get('modelos/criar', [MensagensManuaisController::class, 'create'])
                ->name('modelos.create');

            Route::post('modelos', [MensagensManuaisController::class, 'store'])
                ->name('modelos.store');

            Route::get('modelos/{modelo}/enviar', [MensagensManuaisController::class, 'formEnviar'])
                ->name('modelos.form_enviar');

            Route::post('modelos/{modelo}/enviar', [MensagensManuaisController::class, 'enviar'])
                ->name('modelos.enviar');
        });

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
