<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\Produto;
use App\Models\ContasReceber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $totClientes     = Cliente::count();
        $totRevendedoras = Revendedora::count();
        $totProdutos     = Produto::count();

        // ===== Contas a Receber em aberto (detecção automática) =====
        $query = ContasReceber::query();
        $table = (new ContasReceber())->getTable();

        // 1) status = 'aberto'
        if (Schema::hasColumn($table, 'status')) {
            $query->where('status', 'aberto');
        }
        // 2) pago = 0
        elseif (Schema::hasColumn($table, 'pago')) {
            $query->where('pago', 0);
        }
        // 3) data_baixa IS NULL
        elseif (Schema::hasColumn($table, 'data_baixa')) {
            $query->whereNull('data_baixa');
        }

        // Nome da coluna do valor (ajuste se o seu campo for diferente)
        $colValor = Schema::hasColumn($table, 'valor') ? 'valor'
                   : (Schema::hasColumn($table, 'valor_titulo') ? 'valor_titulo' : null);

        $crEmAberto  = (clone $query)->count();
        $valorAberto = $colValor ? (clone $query)->sum($colValor) : 0;

        return view('dashboard', compact(
            'totClientes',
            'totRevendedoras',
            'totProdutos',
            'crEmAberto',
            'valorAberto'
        ));
    }
}
