<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Models\PedidoVenda;
use App\Models\ItemVenda;
use App\Models\Cliente;
use App\Models\Revendedora;
use App\Models\FormaPagamento;
use App\Models\PlanoPagamento;
use App\Models\Produto;
use App\Models\ViewProduto;
use App\Models\Campanha;
use App\Models\CampanhaPremio;
use App\Models\Indicacao;
use App\Models\ContasReceber;
use App\Services\MensageriaService;
use App\Services\Whatsapp\MensagensCampanhaService;

use App\Services\EstoqueService;
use App\Services\ContasReceberService;
use App\Services\CampaignEvaluatorService;
use App\Services\Importacao\PedidoWhatsappParser;

use Illuminate\Http\JsonResponse;

class PedidoVendaController extends Controller
{
    private EstoqueService $estoque;
    private ContasReceberService $cr;

    public function __construct(
        EstoqueService $estoque,
        ContasReceberService $cr,
    ) {
        $this->estoque = $estoque;
        $this->cr      = $cr;
    }

    private function empresaIdOrFail(): int
    {
        // 1) usuário logado
        $empresaId = (int)(Auth::user()?->empresa_id ?? 0);

        // 2) fallback: empresa ativa no container (middleware EmpresaAtiva)
        if ($empresaId <= 0 && app()->bound('empresa')) {
            $empresaId = (int)(app('empresa')->id ?? 0);
        }

        if ($empresaId <= 0) {
            abort(403, 'Empresa ativa não definida.');
        }

        return $empresaId;
    }


    /**
     * Lista pedidos (com filtros: cliente, período e status)
     */
    public function index(Request $request)
    {
        // pega empresa do usuário logado
        $empresaId = $request->user()->empresa_id ?? null;

        $query = \App\Models\PedidoVenda::with(['cliente', 'revendedora'])
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // OU, se preferir, usando o scope:
        // $query = \App\Models\PedidoVenda::daEmpresa()
        //     ->with(['cliente', 'revendedora']);

        // Filtro por cliente (nome)
        if ($request->filled('cliente')) {
            $busca = trim($request->cliente);
            $query->whereHas('cliente', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%");
            });
        }

        // Filtro por período
        if ($request->filled('data_ini')) {
            $query->whereDate('data_pedido', '>=', $request->data_ini);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_pedido', '<=', $request->data_fim);
        }

        // Filtro por status
        if ($request->filled('status')) {
            $status = strtoupper($request->status);
            $query->where('status', $status);
        }

        $pedidos = $query
            ->orderByDesc('data_pedido')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('vendas.index', compact('pedidos'));
    }

    public function show(Request $request, int $id)
    {
        // empresa atual (mesma lógica do seu sidebar)
        $empresaId = $request->user()->empresa_id ?? null;

        if (!$empresaId && app()->bound('empresa')) {
            $empresaId = app('empresa')->id ?? null;
        }

        $pedido = PedidoVenda::query()
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->with([
                'cliente',
                'indicador',
                'revendedora',
                'forma',
                'plano',
                'itens.produto',
                'contasReceber',
            ])
            ->findOrFail($id);

        $itens = $pedido->itens ?? collect();

        /**
         * ÚLTIMA COMPRA por produto (qtd e data)
         * Requer MySQL 8+ por causa do ROW_NUMBER().
         */
        $productIds = $itens->pluck('produto_id')->filter()->unique()->values();
        $mapUltCompra = collect();

        if ($empresaId && $productIds->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, $productIds->count(), '?'));

            $sql = "
            SELECT produto_id, ultima_qtd, ultima_data
            FROM (
                SELECT
                    cp.produto_id,
                    cp.quantidade AS ultima_qtd,
                    COALESCE(c.data_compra, c.data_emissao, c.created_at) AS ultima_data,
                    ROW_NUMBER() OVER (
                        PARTITION BY cp.produto_id
                        ORDER BY COALESCE(c.data_compra, c.data_emissao, c.created_at) DESC, c.id DESC, cp.id DESC
                    ) AS rn
                FROM appcompraproduto cp
                JOIN appcompra c ON c.id = cp.compra_id
                WHERE c.empresa_id = ?
                  AND cp.produto_id IN ($placeholders)
            ) t
            WHERE rn = 1
        ";

            $rows = DB::select($sql, array_merge([$empresaId], $productIds->all()));
            $mapUltCompra = collect($rows)->keyBy('produto_id');
        }

        foreach ($itens as $it) {
            $row = $mapUltCompra->get($it->produto_id);
            $it->ultima_compra_qtd  = $row->ultima_qtd  ?? null;
            $it->ultima_compra_data = $row->ultima_data ?? null;
        }

        /**
         * TOTAIS / RENTABILIDADE / PONTOS
         */
        $totais = [
            'qtd_itens' => (int) $itens->sum('quantidade'),

            'total_itens' => (float) $itens->sum('preco_total'),
            'desc_itens'  => (float) $itens->sum('valor_desconto'),

            'receita_liquida_itens' => (float) $itens->sum(function ($it) {
                return (float)($it->preco_total ?? 0) - (float)($it->valor_desconto ?? 0);
            }),

            'custo_total' => (float) $itens->sum(function ($it) {
                $qtd = (float)($it->quantidade ?? 0);
                $custoUnit = (float)($it->produto->preco_compra ?? 0);
                return $qtd * $custoUnit;
            }),

            'pontos'       => (int) $itens->sum('pontuacao'),
            'pontos_total' => (int) $itens->sum('pontuacao_total'),
        ];

        $totais['lucro_total'] = $totais['receita_liquida_itens'] - $totais['custo_total'];
        $totais['margem_total'] = $totais['receita_liquida_itens'] > 0
            ? ($totais['lucro_total'] / $totais['receita_liquida_itens']) * 100
            : 0;

        return view('vendas.show', compact('pedido', 'totais'));
    }

    private function normalizeMovEnums(string $tipo, ?string $status): array
    {
        // normaliza tipo_mov
        $tipo = strtoupper(trim($tipo));
        $mapTipo = [
            'RESERVA_SAIDA'   => 'RESERVA_SAIDA',
            'RESERVA- SAIDA'  => 'RESERVA_SAIDA',
            'SAIDA_RESERVA'   => 'RESERVA_SAIDA',
            'RESERVA_ENTRADA' => 'RESERVA_ENTRADA',
            'RESERVA- ENTRADA' => 'RESERVA_ENTRADA',
            'ENTRADA_RESERVA' => 'RESERVA_ENTRADA',
            'ENTRADA'         => 'ENTRADA',
            'SAIDA'           => 'SAIDA',
            'AJUSTE'          => 'AJUSTE',
            // sinônimos antigos:
            'RESERVA'         => 'RESERVA_SAIDA',
            'DEVOLUCAO'       => 'ENTRADA',
            'BAIXA'           => 'SAIDA',
        ];
        $tipo = $mapTipo[$tipo] ?? $tipo;

        // normaliza status
        $status = $status !== null ? strtoupper(trim($status)) : null;
        $mapStatus = [
            'RESERVADO'     => 'RESERVADO',
            'CONFIRMADO'    => 'CONFIRMADO',
            'CANCELADO'     => 'CANCELADO',
            // sinônimos antigos que causavam truncation:
            'RESERVA'       => 'RESERVADO',
            'CANCELAMENTO'  => 'CANCELADO',
            'CONFIRMADA'    => 'CONFIRMADO',
        ];
        if ($status !== null) {
            $status = $mapStatus[$status] ?? $status;
        }

        return [$tipo, $status];
    }

    /**
     * Formulário de novo pedido
     */
    public function create(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario?->empresa_id;

        // Clientes e revendedoras apenas da empresa atual
        $clientes     = Cliente::daEmpresa()
            ->orderBy('nome')
            ->get(['id', 'nome', 'indicador_id']);

        $revendedoras = Revendedora::daEmpresa()
            ->orderBy('nome')
            ->get(['id', 'nome']);

        // Por enquanto, formas e produtos sem filtro de empresa
        $formas   = FormaPagamento::orderBy('nome')->get(['id', 'nome']);
        $produtos = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        $revendedoraPadraoId = Revendedora::where('revenda_padrao', 1)->value('id');

        return view('vendas.create', compact(
            'clientes',
            'revendedoras',
            'formas',
            'produtos',
            'revendedoraPadraoId'
        ));
    }


    /**
     * Salva um novo pedido (status PENDENTE) e RESERVA estoque
     * + aplica campanhas no salvar (idempotente por reavaliação)
     */
    public function store(Request $request)
    {
        $TAB_PEDIDO  = 'apppedidovenda';
        $TAB_ITENS   = 'appitemvenda';

        $empresaId = $this->empresaIdOrFail();

        $data = $request->validate([
            'cliente_id'          => 'required|integer',
            'revendedora_id'      => 'nullable|integer',
            'forma_pagamento_id'  => 'required|integer',
            'plano_pagamento_id'  => 'required|integer',
            'data_pedido'         => 'required|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string|max:1000',
            'desconto'            => 'nullable|numeric|min:0',
            'enviar_msg_cliente'  => 'nullable|boolean',
            'itens'               => 'required|array|min:1',
            'itens.*.produto_id'  => 'required|integer',
            'itens.*.codfabnumero' => 'nullable|string',
            'itens.*.quantidade'  => 'required|integer|min:1',
            'itens.*.preco_unitario' => 'required|numeric|min:0',
            'itens.*.pontuacao'   => 'nullable|integer|min:0',
        ]);

        $enviarMsgCliente = array_key_exists('enviar_msg_cliente', $data)
            ? (bool)$data['enviar_msg_cliente']
            : true;

        return DB::transaction(function () use ($data, $TAB_PEDIDO, $TAB_ITENS, $enviarMsgCliente, $empresaId) {

            $totalBruto       = 0.0;
            $totalPontosUnit  = 0;
            $totalPontosGeral = 0;
            $itensCalc        = [];

            foreach ($data['itens'] as $idx => $item) {
                $codfab = $item['codfabnumero'] ?? null;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (!$vp) {
                    abort(422, "Produto inválido na linha " . ($idx + 1) . " (código/ficha não encontrado).");
                }

                $qtd       = (int) $item['quantidade'];
                $precoReq  = isset($item['preco_unitario']) ? (float) $item['preco_unitario'] : null;
                $pontosReq = isset($item['pontuacao']) ? (int) $item['pontuacao'] : null;

                $precoUnit  = $precoReq  !== null ? $precoReq  : (float) $vp->preco_revenda;
                $pontosUnit = $pontosReq !== null ? $pontosReq : (int) $vp->pontos;

                $totalLinha  = $qtd * $precoUnit;
                $pontosLinha = $qtd * $pontosUnit;

                $totalBruto       += $totalLinha;
                $totalPontosUnit  += $pontosUnit;
                $totalPontosGeral += $pontosLinha;

                $itensCalc[] = [
                    'produto_id'       => $item['produto_id'],
                    'codfabnumero'     => $codfab ?? $vp->codigo_fabrica,
                    'quantidade'       => $qtd,
                    'preco_unitario'   => $precoUnit,
                    'preco_total'      => $totalLinha,
                    'pontuacao'        => $pontosUnit,
                    'pontuacao_total'  => $pontosLinha,
                ];
            }

            $descontoManual = (float) ($data['desconto'] ?? 0);
            $totalLiquido   = max(0, $totalBruto - $descontoManual);

            $cliente     = Cliente::find($data['cliente_id']);
            $indicadorId = (int) ($cliente->indicador_id ?? 1);

            // ✅ grava SEMPRE empresa_id
            $vendaId = DB::table($TAB_PEDIDO)->insertGetId([
                'cliente_id'         => $data['cliente_id'],
                'revendedora_id'     => $data['revendedora_id'] ?? null,
                'forma_pagamento_id' => $data['forma_pagamento_id'],
                'plano_pagamento_id' => $data['plano_pagamento_id'],
                'data_pedido'        => $data['data_pedido'],
                'previsao_entrega'   => $data['previsao_entrega'] ?? null,
                'observacao'         => $data['observacao'] ?? null,
                'valor_total'        => $totalBruto,
                'valor_desconto'     => $descontoManual,
                'valor_liquido'      => $totalLiquido,
                'pontuacao'          => $totalPontosUnit,
                'pontuacao_total'    => $totalPontosGeral,
                'status'             => 'PENDENTE',
                'indicador_id'       => $indicadorId,
                'enviar_msg_cliente' => $enviarMsgCliente ? 1 : 0,
                'empresa_id'         => $empresaId,
            ]);

            $pedido = PedidoVenda::find($vendaId);

            if ($pedido) {
                $primeiraCompraIndicada = $this->ehPrimeiraCompraIndicada($pedido);
                $campanhaId             = null;

                if ($primeiraCompraIndicada) {
                    $campanhaId = $this->getCampanhaIndicacaoId();
                }

                if ($primeiraCompraIndicada && $campanhaId) {
                    $valorTotal    = (float) ($pedido->valor_total ?? 0);
                    $descontoAtual = (float) ($pedido->valor_desconto ?? 0);

                    $percentualDesconto = $this->getPercentualDescontoCampanha($campanhaId);

                    $descontoIndicacao = round($valorTotal * $percentualDesconto, 2);

                    $novoDesconto     = $descontoAtual + $descontoIndicacao;
                    $novoValorLiquido = max(0, $valorTotal - $novoDesconto);

                    $pedido->valor_desconto = $novoDesconto;
                    $pedido->valor_liquido  = $novoValorLiquido;
                    $pedido->campanha_id    = $campanhaId;

                    $obsOriginal = trim($pedido->observacao ?? '');
                    $percentualLabel = number_format($percentualDesconto * 100, 2, ',', '');
                    $obsCampanha = "Desconto de {$percentualLabel}% aplicado (primeira compra indicada).";

                    $pedido->observacao = $obsOriginal
                        ? $obsOriginal . ' | ' . $obsCampanha
                        : $obsCampanha;

                    $pedido->save();

                    $valorPedidoIndicacao = $novoValorLiquido;

                    if ($valorPedidoIndicacao > 0) {
                        $indicacao = Indicacao::where('indicado_id', $pedido->cliente_id)
                            ->where('pedido_id', $pedido->id)
                            ->first();

                        if (!$indicacao) {
                            $indicacao = new Indicacao();
                            $indicacao->indicado_id  = $pedido->cliente_id;
                            $indicacao->indicador_id = (int) ($pedido->indicador_id ?? 1);
                            $indicacao->pedido_id    = $pedido->id;
                            $indicacao->status       = 'pendente';
                        }

                        if ($indicacao->status !== 'pago') {
                            $indicacao->valor_pedido = $valorPedidoIndicacao;
                            $indicacao->valor_premio = $this->calcularPremioIndicacao($valorPedidoIndicacao);
                            $indicacao->campanha_id  = $campanhaId;
                            $indicacao->save();

                            try {
                                $this->enviarAvisoIndicadorWhatsApp($indicacao);
                            } catch (\Throwable $e) {
                                Log::warning('Erro ao enviar WhatsApp para indicador na criação do pedido', [
                                    'pedido_id'    => $pedido->id,
                                    'indicacao_id' => $indicacao->id ?? null,
                                    'erro'         => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }

                if ($pedido && ($pedido->enviar_msg_cliente ?? true)) {
                    try {
                        $this->enviarAvisoClientePedidoCriado($pedido);
                    } catch (\Throwable $e) {
                        Log::warning('Erro ao enviar WhatsApp para cliente na criação do pedido', [
                            'pedido_id' => $pedido->id ?? null,
                            'erro'      => $e->getMessage(),
                        ]);
                    }
                }
            }

            foreach ($itensCalc as $it) {
                $precoTotal = $it['preco_total'] ?? ($it['preco_unitario'] * $it['quantidade']);

                DB::table($TAB_ITENS)->insert([
                    'pedido_id'        => $vendaId,
                    'produto_id'       => $it['produto_id'],
                    'codfabnumero'     => $it['codfabnumero'],
                    'quantidade'       => $it['quantidade'],
                    'preco_unitario'   => $it['preco_unitario'],
                    'preco_total'      => $precoTotal,
                    'pontuacao'        => $it['pontuacao'],
                    'pontuacao_total'  => $it['pontuacao_total'],
                ]);
            }

            // Carrega pedido com composição de kits e reserva pelo service (agora por empresa)
            $pedidoReserva = PedidoVenda::with('itens.produto.itensDoKit.produtoItem')->find($vendaId);
            if ($pedidoReserva) {
                // ✅ garantia extra: se por algum motivo veio null, força empresa
                if (empty($pedidoReserva->empresa_id)) {
                    $pedidoReserva->empresa_id = $empresaId;
                    $pedidoReserva->save();
                }

                $this->estoque->reservarVenda($pedidoReserva);
            }

            return redirect()
                ->route('vendas.index')
                ->with('success', 'Venda salva com sucesso (totais recalculados e estoque reservado).');
        });
    }

    /**
     * Recebe o texto do WhatsApp e devolve os itens parseados em JSON
     */
    public function importarTextoWhatsapp(Request $request, PedidoWhatsappParser $parser)
    {
        $texto = (string) $request->input('texto', '');

        if (!trim($texto)) {
            return response()->json([
                'success' => false,
                'message' => 'Texto vazio. Cole a mensagem do pedido.'
            ], 422);
        }

        try {
            // O serviço devolve algo como:
            // [ ['codigo' => '6587', 'quantidade' => 1, 'preco' => 25.90, 'descricao' => '...'], ... ]
            $itens = $parser->parse($texto);

            if (empty($itens)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum item foi identificado no texto informado.'
                ], 200);
            }

            // Normaliza para o formato que o JS espera:
            $itensNormalizados = collect($itens)->map(function ($item) {
                return [
                    'codigo'         => $item['codigo'] ?? null,
                    'quantidade'     => (float) ($item['quantidade'] ?? 0),
                    'preco_unitario' => isset($item['preco']) ? (float) $item['preco'] : null,
                    'descricao'      => $item['descricao'] ?? null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'itens'   => $itensNormalizados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao importar texto de pedido WhatsApp', [
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao interpretar o texto do pedido.',
            ], 500);
        }
    }

    /**
     * Editar pedido
     */
    public function edit($id)
    {
        $pedido = PedidoVenda::with(['itens.produto:id,nome,codfabnumero', 'cliente:id,nome', 'revendedora:id,nome'])
            ->findOrFail($id);

        $clientes     = Cliente::orderBy('nome')->get(['id', 'nome']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id', 'nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id', 'nome']);
        $planos       = PlanoPagamento::where('formapagamento_id', $pedido->forma_pagamento_id)
            ->orderBy('descricao')
            ->get(['id', 'descricao', 'formapagamento_id', 'parcelas', 'prazo1', 'prazo2', 'prazo3']);
        $produtos     = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        return view('vendas.edit', compact('pedido', 'clientes', 'revendedoras', 'formas', 'planos', 'produtos'));
    }

    /**
     * Atualiza (e reavalia campanhas)
     * Agora refaz as reservas de forma consistente (inclusive para KITs).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'cliente_id'          => 'required|integer|exists:appcliente,id',
            'revendedora_id'      => 'nullable|integer|exists:apprevendedora,id',
            'forma_pagamento_id'  => 'required|integer|exists:appformapagamento,id',
            'plano_pagamento_id'  => 'required|integer|exists:appplanopagamento,id',
            'codplano'            => 'nullable|string|max:20',
            'data_pedido'         => 'nullable|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string',
            'valor_desconto'      => 'nullable|numeric|min:0',
            'enviar_msg_cliente'  => 'nullable|boolean',
            'itens'                      => 'required|array|min:1',
            'itens.*.produto_id'         => 'required|integer|exists:appproduto,id',
            'itens.*.codfabnumero'       => 'nullable|string|max:30',
            'itens.*.quantidade'         => 'required|integer|min:1',
            'itens.*.preco_unitario'     => 'required|numeric|min:0',
            'itens.*.pontuacao'          => 'nullable|integer|min:0',
            'itens.*.pontuacao_total'    => 'nullable|integer|min:0',
            'pontuacao'        => 'nullable|integer|min:0',
            'pontuacao_total'  => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $pedido = PedidoVenda::with('itens.produto.itensDoKit.produtoItem')->findOrFail($id);

            $statusAnterior = strtoupper($pedido->status ?? '');
            $eraPendente    = in_array($statusAnterior, ['PENDENTE', 'ABERTO', 'RESERVADO'], true);

            if ($eraPendente) {
                $this->estoque->cancelarReservaVenda($pedido);
            }

            $total               = 0.0;
            $pontosTotal         = 0;
            $pontosUnitSomatorio = 0;

            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                $total               += $q * $pu;
                $pontosTotal         += $pto;
                $pontosUnitSomatorio += $ptu;
            }

            $desconto = (float)($request->valor_desconto ?? 0);
            $liquido  = max(0, $total - $desconto);

            $pedido->update([
                'cliente_id'         => $request->cliente_id,
                'revendedora_id'     => $request->revendedora_id,
                'data_pedido'        => $request->data_pedido ?: $pedido->data_pedido,
                'previsao_entrega'   => $request->previsao_entrega,
                'status'             => $pedido->status ?? 'PENDENTE',
                'forma_pagamento_id' => $request->forma_pagamento_id,
                'plano_pagamento_id' => $request->plano_pagamento_id,
                'codplano'           => $request->codplano,
                'valor_total'        => $total,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $liquido,
                'pontuacao'          => $pontosUnitSomatorio,
                'pontuacao_total'    => $pontosTotal,
                'observacao'         => $request->observacao,
                'enviar_msg_cliente' => $request->boolean(
                    'enviar_msg_cliente',
                    $pedido->enviar_msg_cliente ?? true
                ),
            ]);

            ItemVenda::where('pedido_id', $pedido->id)->delete();
            foreach ($request->itens as $it) {
                $q   = (int)$it['quantidade'];
                $pu  = (float)$it['preco_unitario'];
                $ptu = (int)($it['pontuacao'] ?? 0);
                $pto = (int)($it['pontuacao_total'] ?? ($q * $ptu));

                ItemVenda::create([
                    'pedido_id'       => $pedido->id,
                    'produto_id'      => $it['produto_id'],
                    'codfabnumero'    => $it['codfabnumero'] ?? null,
                    'quantidade'      => $q,
                    'preco_unitario'  => $pu,
                    'preco_total'     => $q * $pu,
                    'pontuacao'       => $ptu,
                    'pontuacao_total' => $pto,
                    'reservado'       => 0,
                    'entregue'        => 0,
                ]);
            }

            $pedido->load('itens.produto.itensDoKit.produtoItem');

            $statusAtual  = strtoupper($pedido->status ?? '');
            $estaPendente = in_array($statusAtual, ['PENDENTE', 'ABERTO', 'RESERVADO'], true);

            if ($estaPendente) {
                $this->estoque->reservarVenda($pedido);
            }

            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            $pedido->refresh();
            $this->atualizarIndicacaoParaPedido($pedido, false);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido atualizado, reservas/parcelas ajustadas e campanhas reavaliadas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao atualizar: ' . $e->getMessage())->withInput();
        }
    }
    /**
     * Confirma pedido
     */
    public function confirmar($id)
    {
        $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

        if (!in_array(strtoupper($pedido->status), ['PENDENTE', 'ABERTO'])) {
            return back()->with('info', 'Este pedido não está pendente para confirmação.');
        }

        DB::beginTransaction();
        try {
            $pedido->status = 'ENTREGUE';
            $pedido->save();

            $this->estoque->confirmarSaidaVenda($pedido);

            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido confirmado, reserva liberada, estoque baixado e campanhas reavaliadas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Falha ao confirmar pedido: ' . $e->getMessage());
        }
    }

    /**
     * Exporta CSV do pedido
     */
    public function exportar($id): StreamedResponse
    {
        $pedido = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome', 'itens.produto:id,nome,codfabnumero'])
            ->findOrFail($id);

        $nomeCliente     = $pedido->cliente->nome ?? '';
        $nomeRevendedora = $pedido->revendedora->nome ?? '';
        $arquivo         = "pedido_{$pedido->id}.csv";

        return response()->streamDownload(function () use ($pedido, $nomeCliente, $nomeRevendedora) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Pedido', $pedido->id]);
            fputcsv($out, ['Data', \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y')]);
            fputcsv($out, ['Cliente', $nomeCliente]);
            fputcsv($out, ['Revendedora', $nomeRevendedora]);
            fputcsv($out, ['Status', $pedido->status]);
            fputcsv($out, ['Valor Total', number_format((float)$pedido->valor_total, 2, ',', '.')]);
            fputcsv($out, ['Valor Desconto', number_format((float)$pedido->valor_desconto, 2, ',', '.')]);
            fputcsv($out, ['Valor Líquido', number_format((float)$pedido->valor_liquido, 2, ',', '.')]);
            fputcsv($out, ['Pontuação (unit soma)', (int)$pedido->pontuacao]);
            fputcsv($out, ['Pontuação Total', (int)$pedido->pontuacao_total]);
            fputcsv($out, ['Plano (ID)', (int)$pedido->plano_pagamento_id]);
            fputcsv($out, ['Código do Plano', (string)($pedido->codplano ?? '')]);
            fputcsv($out, []);

            fputcsv($out, ['#', 'CODFAB', 'Produto', 'Qtd', 'Pontos', 'Pontos Total', 'R$ Unit', 'R$ Total']);
            foreach ($pedido->itens as $i => $item) {
                fputcsv($out, [
                    $i + 1,
                    $item->codfabnumero ?? ($item->produto->codfabnumero ?? ''),
                    $item->produto->nome ?? '',
                    (int)$item->quantidade,
                    (int)$item->pontuacao,
                    (int)$item->pontuacao_total,
                    number_format((float)$item->preco_unitario, 2, ',', '.'),
                    number_format((float)$item->preco_total, 2, ',', '.'),
                ]);
            }

            fclose($out);
        }, $arquivo, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Exclui pedido e limpa reservas e CR abertas
     */
    public function cancelar(Request $request, $id)
    {
        $data = $request->validate([
            'observacao' => 'required|string|min:5|max:2000',
        ]);

        // === CONFIGURE os nomes conforme seu banco ===
        $TAB_PEDIDO   = 'apppedidovenda';
        $TAB_ITENS    = 'appitemvenda';
        $TAB_ESTOQUE  = 'appestoque';
        $TAB_MOV      = 'appmovestoque';
        $TAB_CR       = 'appcontasreceber';

        $COL_RESERVA  = 'reservado';
        $COL_DISP     = 'disponivel';

        // 🔴 1) Bloqueia cancelamento se a indicação já foi paga
        $indicacaoJaPaga = Indicacao::where('pedido_id', $id)
            ->where('status', 'pago')
            ->exists();

        if ($indicacaoJaPaga) {
            return back()->with('error', 'Não é possível cancelar este pedido, pois o prêmio de indicação já foi pago.');
        }

        DB::transaction(function () use ($id, $data, $TAB_PEDIDO, $TAB_ESTOQUE, $TAB_MOV, $TAB_CR) {

            // 1) Carrega pedido com lock, itens, produtos e composição (kits)
            $pedido = PedidoVenda::with('itens.produto.itensDoKit.produtoItem')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                abort(404, 'Pedido não encontrado.');
            }

            $statusPedido = mb_strtoupper($pedido->status ?? '');

            if ($statusPedido === 'CANCELADO') {
                abort(422, 'Este pedido já está cancelado.');
            }

            // 2) Ajustes de estoque de acordo com o status
            $empresaId = (int) ($pedido->empresa_id ?? 0);

            // fallback: middleware EmpresaAtiva
            if ($empresaId <= 0 && app()->bound('empresa')) {
                $empresaId = (int) (app('empresa')->id ?? 0);
            }

            // fallback: usuário logado
            if ($empresaId <= 0) {
                $empresaId = (int) (auth::user()?->empresa_id ?? 0);
            }

            if ($empresaId <= 0) {
                abort(422, 'Empresa ativa não definida para cancelar o pedido.');
            }

            if (in_array($statusPedido, ['PENDENTE', 'ABERTO', 'RESERVADO'], true)) {

                // Pedido pendente: apenas libera reservas (inclusive de kits) -> já grava empresa_id no service
                $this->estoque->cancelarReservaVenda($pedido);
            } elseif ($statusPedido === 'ENTREGUE') {

                // Pedido entregue: devolve estoque (kit => componentes)
                $colBase = $this->firstWritableColumn($TAB_ESTOQUE, [
                    'estoque_gerencial',
                    'fisico',
                    'qtd_fisica',
                    'qtd_total',
                    'quantidade',
                    'qtd',
                    'estoque'
                ]);

                if ($colBase) {
                    foreach ($pedido->itens as $item) {
                        $qtdItem = (float) ($item->quantidade ?? 0);
                        if ($qtdItem <= 0) {
                            continue;
                        }

                        $produto = $item->produto;

                        $isKit = ($produto?->tipo ?? null) === 'K'
                            && $produto->itensDoKit
                            && $produto->itensDoKit->count() > 0;

                        if ($isKit) {
                            // 🔹 Estorno de KIT: devolve componentes
                            foreach ($produto->itensDoKit as $componente) {
                                $produtoBase = $componente->produtoItem ?? null;
                                if (!$produtoBase) {
                                    continue;
                                }

                                $qtdComp = $qtdItem * (float) ($componente->quantidade ?? 0);
                                if ($qtdComp <= 0) {
                                    continue;
                                }

                                $produtoBaseId = (int) $produtoBase->id;
                                $codfabBase    = $produtoBase->codfabnumero ?? null;

                                // ✅ garante linha de estoque (empresa + produto)
                                DB::table($TAB_ESTOQUE)->updateOrInsert(
                                    ['empresa_id' => $empresaId, 'produto_id' => $produtoBaseId],
                                    [
                                        'codfabnumero'      => $codfabBase,
                                        $colBase            => DB::raw("COALESCE({$colBase},0)"),
                                        'estoque_gerencial' => DB::raw("COALESCE(estoque_gerencial,0)"),
                                        'reservado'         => DB::raw("COALESCE(reservado,0)"),
                                        'avaria'            => DB::raw("COALESCE(avaria,0)"),
                                        'updated_at'        => now(),
                                        'created_at'        => now(),
                                    ]
                                );

                                // ✅ devolve estoque do componente (por empresa)
                                DB::table($TAB_ESTOQUE)
                                    ->where('empresa_id', $empresaId)
                                    ->where('produto_id', $produtoBaseId)
                                    ->increment($colBase, $qtdComp);

                                $mov = [
                                    'empresa_id'   => $empresaId,
                                    'produto_id'   => $produtoBaseId,
                                    'codfabnumero' => $codfabBase,
                                    'tipo_mov'     => 'ENTRADA',
                                    'status'       => 'CANCELADO',
                                    'origem'       => 'VENDA',
                                    'quantidade'   => $qtdComp,
                                    'data_mov'     => now(),
                                    'observacao'   => $data['observacao'] . ' (estorno de venda entregue - componente do kit)',
                                ];

                                $this->safeInsertMov($TAB_MOV, $mov, $id);
                            }
                        } else {
                            // 🔹 Produto normal: devolve o próprio item
                            $produtoId = (int) $item->produto_id;
                            $codfab    = $item->codfabnumero ?? ($produto->codfabnumero ?? null);

                            // ✅ garante linha de estoque (empresa + produto)
                            DB::table($TAB_ESTOQUE)->updateOrInsert(
                                ['empresa_id' => $empresaId, 'produto_id' => $produtoId],
                                [
                                    'codfabnumero'      => $codfab,
                                    $colBase            => DB::raw("COALESCE({$colBase},0)"),
                                    'estoque_gerencial' => DB::raw("COALESCE(estoque_gerencial,0)"),
                                    'reservado'         => DB::raw("COALESCE(reservado,0)"),
                                    'avaria'            => DB::raw("COALESCE(avaria,0)"),
                                    'updated_at'        => now(),
                                    'created_at'        => now(),
                                ]
                            );

                            // ✅ devolve estoque do item (por empresa)
                            DB::table($TAB_ESTOQUE)
                                ->where('empresa_id', $empresaId)
                                ->where('produto_id', $produtoId)
                                ->increment($colBase, $qtdItem);

                            $mov = [
                                'empresa_id'   => $empresaId,
                                'produto_id'   => $produtoId,
                                'codfabnumero' => $codfab,
                                'tipo_mov'     => 'ENTRADA',
                                'status'       => 'CANCELADO',
                                'origem'       => 'VENDA',
                                'quantidade'   => $qtdItem,
                                'data_mov'     => now(),
                                'observacao'   => $data['observacao'],
                            ];

                            $this->safeInsertMov($TAB_MOV, $mov, $id);
                        }
                    }
                }
            }

            // 4) Pedido -> CANCELADO
            DB::table($TAB_PEDIDO)->where('id', $id)->update([
                'status'           => 'CANCELADO',
                'obs_cancelamento' => $data['observacao'],
                'canceled_at'      => now(),
            ]);

            // 5) Títulos (CR) -> CANCELADO
            DB::table($TAB_CR)->where('pedido_id', $id)->update([
                'status'           => 'CANCELADO',
                'obs_cancelamento' => $data['observacao'],
                'canceled_at'      => now(),
            ]);

            // 6) 🔁 Cancelar indicação vinculada (se ainda não estiver paga)
            Indicacao::where('pedido_id', $id)
                ->where('status', '!=', 'pago')
                ->update([
                    'status'        => 'cancelado',
                    'valor_premio'  => 0,
                    'updated_at'    => now(),
                ]);
        });

        return back()->with('success', 'Pedido cancelado, estoque/títulos e indicação ajustados.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // 🔴 1) Se tiver indicação paga, não deixa excluir
            $indicacaoJaPaga = Indicacao::where('pedido_id', $id)
                ->where('status', 'pago')
                ->exists();

            if ($indicacaoJaPaga) {
                DB::rollBack();
                return redirect()->route('vendas.index')
                    ->with('error', 'Não é possível excluir este pedido, pois o prêmio de indicação já foi pago.');
            }

            $pedido = PedidoVenda::with('itens.produto')->findOrFail($id);

            if (strtoupper($pedido->status) === 'PENDENTE') {
                $this->estoque->cancelarReservaVenda($pedido);
            }

            $this->cr->cancelarAbertasPorPedido($pedido->id);

            // 🔁 2) Apaga indicação associada, se não estiver paga
            Indicacao::where('pedido_id', $pedido->id)
                ->where('status', '!=', 'pago')
                ->delete();

            ItemVenda::where('pedido_id', $pedido->id)->delete();
            $pedido->delete();

            DB::commit();
            return redirect()->route('vendas.index')->with('success', 'Pedido excluído com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('vendas.index')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    private function enumValues(string $table, string $column): array
    {
        $row = DB::selectOne("
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ", [$table, $column]);

        if (!$row || !isset($row->COLUMN_TYPE)) return [];
        if (preg_match("/^enum\\((.*)\\)$/i", $row->COLUMN_TYPE, $m)) {
            $vals = array_map(fn($v) => trim($v, " '"), explode(',', $m[1]));
            return array_map('strtoupper', $vals);
        }
        return [];
    }

    private function safeInsertMov(string $table, array $mov, ?int $pedidoId = null): void
    {
        // =========================
        // 0) empresa_id (se existir)
        // =========================
        if (Schema::hasColumn($table, 'empresa_id') && empty($mov['empresa_id'])) {
            $empresaId = 0;

            // 1) pedido (quando existir no payload, ou quando o caller passar junto)
            if (isset($mov['pedido']) && is_object($mov['pedido']) && isset($mov['pedido']->empresa_id)) {
                $empresaId = (int) ($mov['pedido']->empresa_id ?? 0);
                unset($mov['pedido']); // não grava objeto no insert
            }

            // 2) app('empresa') (middleware EmpresaAtiva)
            if ($empresaId <= 0 && app()->bound('empresa')) {
                $empresaId = (int) (app('empresa')->id ?? 0);
            }

            // 3) usuário logado
            if ($empresaId <= 0) {
                $u = Auth::user();
                $empresaId = (int) ($u?->empresa_id ?? 0);
            }

            if ($empresaId > 0) {
                $mov['empresa_id'] = $empresaId;
            }
        }

        // =========================
        // 1) normaliza/enforce enums
        // =========================
        if (isset($mov['tipo_mov'])) {
            $allowedTipo = $this->enumValues($table, 'tipo_mov');
            $mov['tipo_mov'] = strtoupper(trim((string)$mov['tipo_mov']));
            if (!empty($allowedTipo) && !in_array($mov['tipo_mov'], $allowedTipo, true)) {
                unset($mov['tipo_mov']);
            }
        }

        if (isset($mov['status'])) {
            $allowedStatus = $this->enumValues($table, 'status');
            $mov['status'] = strtoupper(trim((string)$mov['status']));
            if (!empty($allowedStatus) && !in_array($mov['status'], $allowedStatus, true)) {
                unset($mov['status']);
            }
        }

        if (isset($mov['origem'])) {
            $allowedOrigem = $this->enumValues($table, 'origem');
            $mov['origem'] = strtoupper(trim((string)$mov['origem']));
            if (!empty($allowedOrigem) && !in_array($mov['origem'], $allowedOrigem, true)) {
                unset($mov['origem']);
            }
        }

        // =========================
        // 2) origem_id / pedido_id
        // =========================
        if ($pedidoId !== null) {
            if (Schema::hasColumn($table, 'origem_id') && empty($mov['origem_id'])) {
                $mov['origem_id'] = $pedidoId;
            }
            if (Schema::hasColumn($table, 'pedido_id') && empty($mov['pedido_id'])) {
                $mov['pedido_id'] = $pedidoId;
            }
        }

        // =========================
        // 3) timestamps (se existirem)
        // =========================
        $now = now();
        if (Schema::hasColumn($table, 'created_at') && empty($mov['created_at'])) {
            $mov['created_at'] = $now;
        }
        if (Schema::hasColumn($table, 'updated_at') && empty($mov['updated_at'])) {
            $mov['updated_at'] = $now;
        }

        DB::table($table)->insert($mov);
    }

    private function isGeneratedColumn(string $table, string $column): bool
    {
        $row = DB::selectOne("
        SELECT EXTRA
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ", [$table, $column]);

        if (!$row || !isset($row->EXTRA)) return false;
        $extra = strtoupper($row->EXTRA);
        return str_contains($extra, 'GENERATED');
    }

    private function firstWritableColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c) && !$this->isGeneratedColumn($table, $c)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Calcula o valor do prêmio de indicação pela faixa de valor do pedido,
     * usando o percentual configurado na tabela appcampanha_premio.
     *
     * @param  float  $valorLiquido  Valor líquido do pedido do cliente indicado
     * @return float Valor do prêmio em reais (ex.: 8.00)
     */
    private function calcularPremioIndicacao(float $valorLiquido): float
    {
        // 1) Descobre a campanha de indicação ativa
        $campanhaId = $this->getCampanhaIndicacaoId();

        if (!$campanhaId) {
            Log::info('calcularPremioIndicacao: nenhuma campanha de indicação ativa encontrada.');
            return 0.0;
        }

        // 2) Busca a faixa compatível com o valor do pedido
        $faixa = CampanhaPremio::query()
            ->where('campanha_id', $campanhaId)
            ->where('faixa_inicio', '<=', $valorLiquido)
            ->where('faixa_fim', '>=', $valorLiquido)
            ->orderBy('faixa_inicio')
            ->first();

        if (!$faixa) {
            Log::info('calcularPremioIndicacao: nenhuma faixa encontrada', [
                'campanha_id'   => $campanhaId,
                'valor_liquido' => $valorLiquido,
            ]);

            return 0.0;
        }

        // 3) Percentual de prêmio (ex.: 5, 6, 7...)
        $percentual = (float) $faixa->valor_premio;

        // 4) Calcula o valor em reais
        $valorPremio = $valorLiquido * $percentual / 100;

        // 5) Arredonda para 2 casas decimais
        return round($valorPremio, 2);
    }

    /**
     * Retorna o percentual de desconto da campanha (campo perc_desc da appcampanha)
     * em formato decimal para cálculo.
     *
     * Ex.:
     *  - perc_desc = 0.05 => retorna 0.05 (5%)
     *  - perc_desc = 5    => retorna 0.05 (5%)  [caso você prefira gravar 5 no banco]
     */
    private function getPercentualDescontoCampanha(int $campanhaId): float
    {
        try {
            $percDesc = DB::table('appcampanha')
                ->where('id', $campanhaId)
                ->value('perc_desc');

            if ($percDesc === null) {
                Log::warning('Campanha sem perc_desc definido', [
                    'campanha_id' => $campanhaId,
                ]);

                // fallback: 0% se não tiver nada no banco
                return 0.00;
            }

            $percDesc = (float) $percDesc;

            // Se a pessoa gravar "5" ao invés de "0.05", converte pra decimal
            if ($percDesc > 1) {
                $percDesc = $percDesc / 100;
            }

            return $percDesc;
        } catch (\Throwable $e) {
            Log::warning('Erro ao buscar perc_desc da campanha: ' . $e->getMessage(), [
                'campanha_id' => $campanhaId,
            ]);

            // fallback de segurança
            return 0.00;
        }
    }

    /**
     * Busca o ID da campanha de indicação (se existir).
     * Regra: campanha ativa cujo tipo tenha metodo_php = 'isCampanhaIndicacao'
     */
    private function getCampanhaIndicacaoId(): ?int
    {
        try {
            $id = DB::table('appcampanha as c')
                ->join('appcampanha_tipo as t', 't.id', '=', 'c.tipo_id')
                ->where('t.metodo_php', 'isCampanhaIndicacao')
                ->where('c.ativa', 1)
                ->orderByDesc('c.prioridade')
                ->value('c.id');

            Log::debug('DEBUG getCampanhaIndicacaoId', [
                'campanha_id' => $id,
            ]);

            if (!$id) {
                Log::warning('Nenhuma campanha de indicação ativa encontrada em appcampanha/appcampanha_tipo (metodo_php = isCampanhaIndicacao)');
            }

            return $id ?: null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar campanha de indicação: ' . $e->getMessage());
            return null;
        }
    }

    // Retorna true se ESTE pedido for a primeira compra concluída de um cliente indicado (indicador_id != 1).
    private function ehPrimeiraCompraIndicada(PedidoVenda $pedido): bool
    {
        $indicadorId = (int) ($pedido->indicador_id ?? 1);

        // 1) Se não tem indicador "válido", não entra na campanha
        if ($indicadorId === 1) {
            return false;
        }

        // 2) Cliente novo de verdade = sem nenhum outro pedido no banco
        $jaTemQualquerPedido = PedidoVenda::where('cliente_id', $pedido->cliente_id)
            ->where('id', '!=', $pedido->id)
            ->exists();

        if ($jaTemQualquerPedido) {
            return false;
        }

        // Chegou aqui: cliente indicado e sem nenhum outro pedido cadastrado
        return true;
    }

    /**
     * Cria/atualiza o registro em appindicacao para este pedido.
     * - Só atua quando indicador_id != 1
     * - Só atua se o pedido estiver ENTREGUE
     * - Se já houver indicação PAGA, não mexe
     * - Se não houver ainda indicação e $criarSeNaoExistir = false, não cria
     */
    function atualizarIndicacaoParaPedido(PedidoVenda $pedido, bool $criarSeNaoExistir = false): void
    {
        // Só calcula para pedido ENTREGUE
        if (strtoupper($pedido->status ?? '') !== 'ENTREGUE') {
            return;
        }

        $indicadorId = (int) ($pedido->indicador_id ?? 1);

        // Regra: só se indicador for diferente de 1
        if ($indicadorId === 1) {
            return;
        }

        $valorPedido = (float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0);
        if ($valorPedido <= 0) {
            return;
        }

        // Campanha de indicação (se existir)
        $campanhaIndicacaoId = $this->getCampanhaIndicacaoId();

        // Busca indicação EXISTENTE para ESTE cliente E ESTE pedido
        $indicacao = Indicacao::where('indicado_id', $pedido->cliente_id)
            ->where('pedido_id', $pedido->id)
            ->first();

        // Se não existe e não é para criar, sai
        if (!$indicacao && !$criarSeNaoExistir) {
            return;
        }

        // Se não existe e pode criar, instancia
        if (!$indicacao) {
            $indicacao = new Indicacao();
            $indicacao->indicado_id  = $pedido->cliente_id;
            $indicacao->indicador_id = $indicadorId;
            $indicacao->pedido_id    = $pedido->id;
            $indicacao->status       = 'pendente';
        }

        // Se já está pago, não recalcula (já foi pago o PIX)
        if ($indicacao->status === 'pago') {
            return;
        }

        $indicacao->valor_pedido = $valorPedido;
        $indicacao->valor_premio = $this->calcularPremioIndicacao($valorPedido);

        // Amarra à campanha de indicação, se existir
        if ($campanhaIndicacaoId && !$indicacao->campanha_id) {
            $indicacao->campanha_id = $campanhaIndicacaoId;
        }

        $indicacao->save();
    }

    /**
     * Monta e envia o recibo de ENTREGA via WhatsApp usando MensageriaService.
     * - Informa que o pedido foi ENTREGUE
     * - Valor final
     * - Vencimento das parcelas
     * - Observação do pedido (se houver)
     */
    function enviarReciboWhatsApp(PedidoVenda $pedido): void
    {
        // Garante cliente carregado
        $pedido->loadMissing('cliente');

        $cliente = $pedido->cliente;
        if (!$cliente) {
            return;
        }

        $valor = (float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0);

        $dataPedido  = $pedido->data_pedido
            ? Carbon::parse($pedido->data_pedido)->format('d/m/Y')
            : now()->format('d/m/Y');

        // Data efetiva da entrega (usamos "agora", pois não há campo específico)
        $dataEntrega = now()->format('d/m/Y');

        // 1) Busca as parcelas de contas a receber ligadas a esse pedido.
        $parcelas = ContasReceber::where('pedido_id', $pedido->id)
            ->orderBy('data_vencimento')
            ->get();

        // 📝 TEXTO DA MENSAGEM PARA O CLIENTE
        $mensagem  = "Olá {$cliente->nome}! 👋\n\n";
        $mensagem .= "Seu pedido nº *{$pedido->id}* foi *ENTREGUE* em {$dataEntrega}. 🎉\n";
        $mensagem .= "Ele foi registrado em {$dataPedido}.\n";
        $mensagem .= "Valor final: *R$ " . number_format($valor, 2, ',', '.') . "*.\n\n";

        if ($parcelas->count() > 0) {
            $mensagem .= "📅 *Detalhes do pagamento:*\n";

            foreach ($parcelas as $index => $parcela) {
                $vencimento = $parcela->data_vencimento ?? null;
                $valorParc  = (float) ($parcela->valor ?? 0);

                $vencFmt = $vencimento
                    ? Carbon::parse($vencimento)->format('d/m/Y')
                    : 'sem data';

                $valorParcFmt = 'R$ ' . number_format($valorParc, 2, ',', '.');

                $numParcela = $index + 1;
                $mensagem  .= "- Parcela {$numParcela}: vence em {$vencFmt} - {$valorParcFmt}\n";
            }

            $mensagem .= "\n";
        }

        if (!empty($pedido->observacao)) {
            $mensagem .= "📝 Observação: {$pedido->observacao}\n\n";
        }

        $mensagem .= "Qualquer dúvida, estou à disposição 😊";

        try {
            /** @var MensageriaService $mensageria */
            $mensageria = app(MensageriaService::class);

            $campanha = $pedido->campanha_id
                ? Campanha::find($pedido->campanha_id)
                : null;

            $msgModel = $mensageria->enviarWhatsapp(
                cliente: $cliente,
                conteudo: $mensagem,
                tipo: 'pedido_entregue_cliente',
                pedido: $pedido,
                campanha: $campanha,
                payloadExtra: [
                    'evento'        => 'pedido_entregue_cliente',
                    'data_entrega'  => $dataEntrega,
                ],
            );

            Log::info('Recibo WhatsApp enviado para cliente (pedido entregue)', [
                'pedido_id'   => $pedido->id,
                'cliente_id'  => $cliente->id,
                'mensagem_id' => $msgModel->id,
                'msg_status'  => $msgModel->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar mensagem de recibo no WhatsApp', [
                'pedido_id'  => $pedido->id,
                'cliente_id' => $cliente->id ?? null,
                'erro'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia mensagem ao INDICADOR informando a primeira compra do indicado
     * e o valor do prêmio da campanha de indicação.
     *
     * Agora usando MensageriaService + MensagensCampanhaService
     * para registrar em `mensagens`.
     */
    private function enviarAvisoIndicadorWhatsApp(Indicacao $indicacao): void
    {
        try {
            // Carrega indicador (quem vai receber o prêmio)
            $indicador = Cliente::find($indicacao->indicador_id);
            if (!$indicador) {
                Log::info('Aviso indicação não enviado: indicador não encontrado', [
                    'indicacao_id' => $indicacao->id ?? null,
                    'indicador_id' => $indicacao->indicador_id ?? null,
                ]);
                return;
            }

            // Nome do indicado (cliente que fez a compra)
            $indicado = Cliente::find($indicacao->indicado_id);
            if (!$indicado) {
                Log::info('Aviso indicação não enviado: indicado não encontrado', [
                    'indicacao_id' => $indicacao->id ?? null,
                    'indicado_id'  => $indicacao->indicado_id ?? null,
                ]);
                return;
            }

            // Pedido e campanha associados à indicação
            $pedido   = PedidoVenda::find($indicacao->pedido_id);
            $campanha = $indicacao->campanha_id
                ? Campanha::find($indicacao->campanha_id)
                : null;

            if (!$pedido) {
                Log::info('Aviso indicação não enviado: pedido não encontrado', [
                    'indicacao_id' => $indicacao->id ?? null,
                    'pedido_id'    => $indicacao->pedido_id ?? null,
                ]);
                return;
            }

            $valorPremio = (float) ($indicacao->valor_premio ?? 0.0);
            if ($valorPremio <= 0) {
                Log::info('Aviso indicação não enviado: valor de prêmio <= 0', [
                    'indicacao_id' => $indicacao->id ?? null,
                    'valor_premio' => $valorPremio,
                ]);
                return;
            }

            /** @var MensageriaService $mensageria */
            $mensageria = app(MensageriaService::class);

            /** @var MensagensCampanhaService $msgCampanha */
            $msgCampanha = app(MensagensCampanhaService::class);

            // Monta o texto usando o service de campanha (com valor do prêmio)
            $texto = $msgCampanha->montarMensagemPedidoPendente(
                indicador: $indicador,
                indicado: $indicado,
                pedido: $pedido,
                valorPremio: $valorPremio,
            );

            // Envia via Mensageria (registra em `mensagens` e manda pelo BotConversa)
            $msgModel = $mensageria->enviarWhatsapp(
                cliente: $indicador,
                conteudo: $texto,
                tipo: 'indicacao_primeira_compra',
                pedido: $pedido,
                campanha: $campanha,
                payloadExtra: [
                    'evento'       => 'indicacao_primeira_compra',
                    'indicacao_id' => $indicacao->id,
                    'valor_premio' => $valorPremio,
                    'valor_pedido' => (float)($indicacao->valor_pedido ?? 0),
                ],
            );

            Log::info('WhatsApp enviado para indicador (campanha de indicação)', [
                'indicacao_id' => $indicacao->id ?? null,
                'indicador_id' => $indicador->id ?? null,
                'telefone'     => $indicador->whatsapp
                    ?? $indicador->telefone
                    ?? $indicador->celular
                    ?? null,
                'mensagem_id'  => $msgModel->id,
                'msg_status'   => $msgModel->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Erro ao enviar WhatsApp para indicador (campanha de indicação)', [
                'indicacao_id' => $indicacao->id ?? null,
                'pedido_id'    => $indicacao->pedido_id ?? null,
                'erro'         => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia mensagem ao CLIENTE assim que o pedido é criado (PENDENTE),
     * informando valor, forma de pagamento e previsão de entrega.
     */
    private function enviarAvisoClientePedidoCriado(PedidoVenda $pedido): void
    {
        try {
            $pedido->loadMissing('cliente', 'forma', 'plano');

            $cliente = $pedido->cliente;
            if (!$cliente) {
                Log::info('Aviso cliente não enviado: pedido sem cliente', [
                    'pedido_id' => $pedido->id ?? null,
                ]);
                return;
            }

            /** @var MensageriaService $mensageria */
            $mensageria = app(MensageriaService::class);

            $campanha = $pedido->campanha_id
                ? Campanha::find($pedido->campanha_id)
                : null;

            $texto = $this->montarMensagemClientePedidoCriado($pedido);

            $msgModel = $mensageria->enviarWhatsapp(
                cliente: $cliente,
                conteudo: $texto,
                tipo: 'pedido_pendente_cliente',
                pedido: $pedido,
                campanha: $campanha,
                payloadExtra: [
                    'evento' => 'pedido_pendente_cliente',
                ],
            );

            Log::info('WhatsApp enviado para cliente (pedido criado)', [
                'pedido_id'   => $pedido->id,
                'cliente_id'  => $cliente->id,
                'mensagem_id' => $msgModel->id,
                'msg_status'  => $msgModel->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Erro ao enviar WhatsApp para cliente (pedido criado)', [
                'pedido_id' => $pedido->id ?? null,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Texto enviado ao CLIENTE na criação do pedido.
     * Informa que o pedido foi registrado, valor, forma de pagamento,
     * previsão de entrega e observação.
     */
    private function montarMensagemClientePedidoCriado(PedidoVenda $pedido): string
    {
        $cliente = $pedido->cliente;
        $nome    = $cliente?->nome ?: 'cliente';

        $dataPedido = optional($pedido->data_pedido)->format('d/m/Y');
        $previsao   = optional($pedido->previsao_entrega)->format('d/m/Y');

        $valor = number_format(
            (float)($pedido->valor_liquido ?? $pedido->valor_total ?? 0),
            2,
            ',',
            '.'
        );

        $formaPg   = $pedido->forma?->nome
            ?? $pedido->forma?->descricao
            ?? 'a forma de pagamento selecionada';

        $planoPg   = $pedido->plano?->nome
            ?? $pedido->plano?->descricao
            ?? null;

        $linhaPlano = $planoPg
            ? "\n💳 Plano de pagamento: *{$planoPg}*"
            : '';

        $linhaPrevisao = $previsao
            ? "\n📅 Previsão de entrega: *{$previsao}*"
            : '';

        $linhaObs = $pedido->observacao
            ? "\n📝 Observação: {$pedido->observacao}"
            : '';

        return "Olá {$nome}! 👋\n\n"
            . "Registramos o seu pedido *#{$pedido->id}* e já estamos providenciando os produtos que você solicitou. 🙌\n\n"
            . "🧾 Data do pedido: *{$dataPedido}*\n"
            . "💰 Valor do pedido: *R$ {$valor}*\n"
            . "💳 Forma de pagamento: *{$formaPg}*"
            . $linhaPlano
            . $linhaPrevisao
            . $linhaObs
            . "\n\nAssim que o pedido for entregue, você receberá uma confirmação por aqui. "
            . "Qualquer dúvida, é só responder esta mensagem. 🙂";
    }

    public function confirmarEntrega(int $id)
    {
        $pedido = PedidoVenda::with('itens.produto', 'cliente')->findOrFail($id);

        $statusAtual = strtoupper($pedido->status ?? '');
        if (!in_array($statusAtual, ['PENDENTE', 'ABERTO', 'RESERVADO'])) {
            return back()->with('info', 'Este pedido não está pendente para confirmação.');
        }

        DB::beginTransaction();

        try {
            // 1) baixa estoque
            $this->estoque->confirmarSaidaVenda($pedido);

            // 2) marca como ENTREGUE
            $pedido->status = 'ENTREGUE';
            $pedido->save();

            // 3) Descobre se este pedido é a PRIMEIRA COMPRA indicada
            $primeiraCompraIndicada = $this->ehPrimeiraCompraIndicada($pedido);

            // 4) Atualiza (ou cria) a indicação para ESTE pedido
            $this->atualizarIndicacaoParaPedido($pedido, $primeiraCompraIndicada);

            // 5) Gera Contas a Receber
            $this->cr->gerarParaPedido($pedido);

            // 6) Reavalia campanhas
            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Falha ao confirmar entrega: ' . $e->getMessage());
        }

        // 7) 🔔 Envia recibo pelo WhatsApp FORA da transação (respeitando flag)
        try {
            $pedido->loadMissing('cliente');

            if ($pedido->enviar_msg_cliente ?? true) {
                $this->enviarReciboWhatsApp($pedido);
            } else {
                Log::info('Recibo WhatsApp NÃO enviado (enviar_msg_cliente = false)', [
                    'pedido_id'          => $pedido->id,
                    'enviar_msg_cliente' => $pedido->enviar_msg_cliente ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Erro ao enviar recibo WhatsApp após confirmação de entrega', [
                'pedido_id' => $pedido->id,
                'erro'      => $e->getMessage(),
            ]);
        }

        return redirect()->route('vendas.index')
            ->with('success', 'Entrega confirmada, CR gerado, campanhas/indicações processadas e recibo enviado pelo WhatsApp (quando permitido).');
    }
}
