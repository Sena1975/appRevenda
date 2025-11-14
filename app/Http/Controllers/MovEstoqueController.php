<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovEstoque;
use App\Models\Produto;
use App\Services\EstoqueService;
use Carbon\Carbon;

class MovEstoqueController extends Controller
{
    private EstoqueService $estoque;

    public function __construct(EstoqueService $estoque)
    {
        $this->estoque = $estoque;
    }

    /**
     * Lista todas as movimentações
     */
    public function index(Request $request)
    {
        // Datas padrão: 1º dia do mês até hoje
        $hoje = Carbon::today()->toDateString();
        $primeiroDiaMes = Carbon::today()->startOfMonth()->toDateString();

        // Se vier na requisição, usa; senão, fica o default
        if ($request->filled('data_ini')) {
            $dataIni = $request->input('data_ini');
        } else {
            $dataIni = $primeiroDiaMes;
        }

        if ($request->filled('data_fim')) {
            $dataFim = $request->input('data_fim');
        } else {
            $dataFim = $hoje;
        }

        // Query base
        $query = MovEstoque::with('produto')
            ->orderBy('data_mov', 'desc');

        // Filtro por produto (código/descrição)
        if ($request->filled('produto')) {
            $busca = trim($request->produto);

            $query->whereHas('produto', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('codfabnumero', 'like', "%{$busca}%");
            });
        }

        // Tipo
        if ($request->filled('tipo')) {
            $query->where('tipo_mov', $request->tipo);
        }

        // Origem
        if ($request->filled('origem')) {
            $query->where('origem', $request->origem);
        }

        // Período (AGORA sempre aplica, com default do mês)
        $query->whereDate('data_mov', '>=', $dataIni)
              ->whereDate('data_mov', '<=', $dataFim);

        $movimentos = $query->paginate(20)->withQueryString();

        $tipos   = ['ENTRADA', 'SAIDA'];
        $origens = ['COMPRA', 'VENDA', 'AJUSTE'];

        // manda as datas pra view
        return view('movestoque.index', compact(
            'movimentos',
            'tipos',
            'origens',
            'dataIni',
            'dataFim'
        ));
    }


    /**
     * Mostra o formulário para registrar nova movimentação
     */
    public function create()
    {
        $produtos = Produto::orderBy('nome')->get(['id','nome','codfabnumero']);
        return view('movestoque.create', compact('produtos'));
    }

    /**
     * Grava uma movimentação manual (ajuste, entrada, saída)
     */
    public function store(Request $request)
    {
        $request->validate([
            'produto_id'    => 'required|integer|exists:appproduto,id',
            'tipo_mov'      => 'required|string|in:ENTRADA,SAIDA,AJUSTE',
            'quantidade'    => 'required|numeric|min:0.01',
            'preco_unitario'=> 'nullable|numeric|min:0',
            'observacao'    => 'nullable|string|max:255',
        ],[
            'produto_id.required' => 'Selecione um produto.',
            'tipo_mov.in'         => 'Tipo inválido. Use ENTRADA, SAIDA ou AJUSTE.',
        ]);

        // Atualiza saldo + registra a movimentação (via service)
        $this->estoque->registrarMovimentoManual(
            (int)$request->produto_id,
            $request->tipo_mov,
            (float)$request->quantidade,
            (float)($request->preco_unitario ?? 0),
            (string)($request->observacao ?? '')
        );

        // Observação: também mantemos um registro pelo Model caso você liste por Eloquent:
        MovEstoque::create([
            'produto_id'     => $request->produto_id,
            'tipo_mov'       => $request->tipo_mov === 'AJUSTE'
                                  ? ((float)$request->quantidade >= 0 ? 'ENTRADA' : 'SAIDA')
                                  : $request->tipo_mov,
            'quantidade'     => $request->tipo_mov === 'SAIDA' ? -abs($request->quantidade) : abs($request->quantidade),
            'preco_unitario' => $request->preco_unitario ?? 0,
            'observacao'     => $request->observacao ?? '',
            'status'         => 'CONFIRMADO',
            'data_mov'       => now(),
        ]);

        return redirect()->route('movestoque.index')->with('success', 'Movimentação registrada com sucesso!');
    }

    /**
     * Mostra detalhes de uma movimentação
     */
    public function show($id)
    {
        $mov = MovEstoque::with('produto')->findOrFail($id);
        return view('movestoque.show', compact('mov'));
    }
}
