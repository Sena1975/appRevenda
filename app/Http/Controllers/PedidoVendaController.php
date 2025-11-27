<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
use App\Models\Indicacao;

use App\Services\EstoqueService;
use App\Services\ContasReceberService;
use App\Services\CampaignEvaluatorService;
use App\Services\Importacao\PedidoWhatsappParser;
use App\Services\Whatsapp\BotConversaService;

use Illuminate\Http\JsonResponse;

class PedidoVendaController extends Controller
{
    private EstoqueService $estoque;
    private ContasReceberService $cr;
    private BotConversaService $whatsapp;

    public function __construct(
        EstoqueService $estoque,
        ContasReceberService $cr,
        BotConversaService $whatsapp
    ) {
        $this->estoque = $estoque;
        $this->cr      = $cr;
        $this->whatsapp = $whatsapp;
    }

    /**
     * Lista pedidos (com filtros: cliente, período e status)
     */
    public function index(Request $request)
    {
        $q = PedidoVenda::with(['cliente:id,nome', 'revendedora:id,nome']);

        // Filtro por status
        $status = strtoupper((string)$request->query('status', ''));
        if ($status === 'PENDENTES') {
            $q->whereIn('status', ['PENDENTE', 'ABERTO']);
        } elseif (in_array($status, ['PENDENTE', 'ABERTO', 'ENTREGUE', 'CANCELADO'])) {
            $q->where('status', $status);
        }

        // (espaço pra filtros extras no futuro, ex: cliente, datas etc.)
        $pedidos = $q->orderByDesc('id')->paginate(10)->appends($request->query());

        return view('vendas.index', compact('pedidos'));
    }

    public function show($id)
    {
        return redirect()->route('vendas.edit', $id);
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
    public function create()
    {
        $clientes     = Cliente::orderBy('nome')->get(['id', 'nome', 'indicador_id']);
        $revendedoras = Revendedora::orderBy('nome')->get(['id', 'nome']);
        $formas       = FormaPagamento::orderBy('nome')->get(['id', 'nome']);
        $produtos     = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        $revendedoraPadraoId = Revendedora::where('revenda_padrao', 1)->value('id'); // pode retornar null

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
        // ===== ajuste rápido de nomes, se seu legado usar outros =====
        $TAB_PEDIDO  = 'apppedidovenda'; // cabeçalho do pedido
        $TAB_ITENS   = 'appitemvenda';    // itens do pedido (troque p/ apppedidovendaitem se for o caso)
        $TAB_ESTOQUE = 'appestoque';     // estoque atual
        $TAB_MOV     = 'appmovestoque';  // movimentações de estoque

        // colunas de estoque
        $COL_DISP    = 'disponivel';
        $COL_RESERVA = 'reservado';      // se for 'reserva' no seu banco, troque aqui

        // ===== 1) Validação =====
        $data = $request->validate([
            'cliente_id'          => 'required|integer',
            'revendedora_id'      => 'nullable|integer',
            'forma_pagamento_id'  => 'required|integer',
            'plano_pagamento_id'  => 'required|integer',
            'data_pedido'         => 'required|date',
            'previsao_entrega'    => 'nullable|date',
            'observacao'          => 'nullable|string|max:1000',
            'desconto'            => 'nullable|numeric|min:0',

            'itens'                        => 'required|array|min:1',
            'itens.*.produto_id'           => 'required|integer',
            'itens.*.codfabnumero'         => 'nullable|string',
            'itens.*.quantidade'           => 'required|integer|min:1',
            'itens.*.preco_unitario'       => 'required|numeric|min:0',
            'itens.*.pontuacao'            => 'nullable|integer|min:0',
        ]);


        return DB::transaction(function () use ($data, $TAB_PEDIDO, $TAB_ITENS, $TAB_ESTOQUE, $TAB_MOV, $COL_DISP, $COL_RESERVA) {

            $totalBruto = 0.0;
            $totalPontosUnit = 0;
            $totalPontosGeral = 0;
            $itensCalc = [];

            // ===== 2) Recalcular itens pela ViewProduto =====
            foreach ($data['itens'] as $idx => $item) {
                $codfab = $item['codfabnumero'] ?? null;

                // Se não vier, aqui você pode resolver pelo produto_id na sua tabela de produtos
                // $codfab = $codfab ?: optional(Produto::find($item['produto_id']))->codfabnumero;

                $vp = $codfab
                    ? ViewProduto::where('codigo_fabrica', $codfab)->first()
                    : null;

                if (!$vp) {
                    abort(422, "Produto inválido na linha " . ($idx + 1) . " (código/ficha não encontrado).");
                }

                $qtd       = (int) $item['quantidade'];
                $precoReq  = isset($item['preco_unitario']) ? (float) $item['preco_unitario'] : null;
                $pontosReq = isset($item['pontuacao']) ? (int) $item['pontuacao'] : null;

                // Usa o que veio da tela; se vier nulo, cai no valor padrão da view
                $precoUnit  = $precoReq  !== null ? $precoReq  : (float) $vp->preco_revenda;
                $pontosUnit = $pontosReq !== null ? $pontosReq : (int) $vp->pontos;

                // Só informação, sem bloquear na criação
                $estoqueAtual = (int) ($vp->qtd_estoque ?? 0);
                // ⚠ Aqui NÃO tem mais abort(). A validação de estoque
                // vai ser feita só na CONFIRMAÇÃO da entrega.


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

            $desconto     = (float) ($data['desconto'] ?? 0);
            $totalLiquido = max(0, $totalBruto - $desconto);

            // pega indicador do cliente (padrão = 1)
            $cliente       = Cliente::find($data['cliente_id']);
            $indicadorId   = (int) ($cliente->indicador_id ?? 1);

            // ===== 3) Grava cabeçalho do pedido (status inicial PENDENTE) =====
            $vendaId = DB::table($TAB_PEDIDO)->insertGetId([
                'cliente_id'         => $data['cliente_id'],
                'revendedora_id'     => $data['revendedora_id'] ?? null,
                'forma_pagamento_id' => $data['forma_pagamento_id'],
                'plano_pagamento_id' => $data['plano_pagamento_id'],
                'data_pedido'        => $data['data_pedido'],
                'previsao_entrega'   => $data['previsao_entrega'] ?? null,
                'observacao'         => $data['observacao'] ?? null,
                'valor_total'        => $totalBruto,
                'valor_desconto'     => $desconto,
                'valor_liquido'      => $totalLiquido,
                'pontuacao'          => $totalPontosUnit,
                'pontuacao_total'    => $totalPontosGeral,
                'status'             => 'PENDENTE',
                'indicador_id'       => $indicadorId,
            ]);

            // ===== 4) Grava itens =====
            foreach ($itensCalc as $it) {
                // calcula com fallback caso 'total' não exista no array
                $precoTotal = ($it['total'] ?? ($it['preco_unitario'] * $it['quantidade']));

                DB::table($TAB_ITENS)->insert([
                    // ATENÇÃO: use o nome da FK que existe na sua tabela
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

            // ===== 5) Reserva de estoque + movimentação (status inicial = PENDENTE) =====
            foreach ($itensCalc as $it) {
                // trava o registro de estoque do produto
                $estq = DB::table($TAB_ESTOQUE)
                    ->lockForUpdate()
                    ->where('codfabnumero', $it['codfabnumero'])
                    ->first();

                if (!$estq) {
                    // cria o registro, se não existir (opcional)
                    DB::table($TAB_ESTOQUE)->insert([
                        'produto_id'   => $it['produto_id'],
                        'codfabnumero' => $it['codfabnumero'],
                        // NÃO enviar 'disponivel' porque é coluna gerada
                        $COL_RESERVA   => 0,
                    ]);

                    // simulamos o objeto só com o campo que vamos usar (reservado)
                    $estq = (object)[$COL_RESERVA => 0];
                }


                // aumenta a reserva
                $novaReserva = ((int)($estq->{$COL_RESERVA} ?? 0)) + (int)$it['quantidade'];
                DB::table($TAB_ESTOQUE)
                    ->where('codfabnumero', $it['codfabnumero'])
                    ->update([$COL_RESERVA => $novaReserva]);

                // registra movimentação: SAÍDA / RESERVA AO CRIAR
                [$tipoOk, $statusOk] = $this->normalizeMovEnums('RESERVA_SAIDA', 'RESERVADO');

                $mov = [
                    'produto_id'   => $it['produto_id'],
                    'codfabnumero' => $it['codfabnumero'],
                    'tipo_mov'     => 'RESERVA_SAIDA',
                    'status'       => 'RESERVADO',
                    'origem'       => 'VENDA',
                    'quantidade'   => (int)$it['quantidade'],
                    'data_mov'     => now(),
                    'observacao'   => 'Reserva de estoque na criação do pedido',
                ];
                $this->safeInsertMov($TAB_MOV, $mov, $vendaId);
            }

            // (CR/financeiro pode ser gerado aqui, se for seu fluxo)
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
            $pedido = PedidoVenda::with('itens')->findOrFail($id);

            // Recalcula totais
            $total = 0.0;
            $pontosTotal = 0;
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
            ]);

            // Recria itens
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

            // Reserva se ainda pendente
            if (strtoupper($pedido->status) === 'PENDENTE') {
                $old = $pedido->load('itens');
                foreach ($old->itens as $itemOld) {
                    DB::table('appestoque')
                        ->where('produto_id', $itemOld->produto_id)
                        ->update([
                            'reservado'  => DB::raw("GREATEST(COALESCE(reservado,0) - {$itemOld->quantidade}, 0)"),
                            'updated_at' => now(),
                        ]);
                }
                DB::table('appmovestoque')
                    ->where('origem', 'VENDA')
                    ->where('origem_id', $pedido->id)
                    ->where('status', 'PENDENTE')
                    ->delete();

                $pedido->load('itens.produto');
                $this->estoque->reservarVenda($pedido);
            }

            // Reavalia campanhas
            $pedido->load('itens');
            $service   = app(CampaignEvaluatorService::class);
            $campanhas = $service->reavaliarPedido($pedido);
            session()->flash('campanhas', $campanhas);

            // 🔹 Atualiza indicação se existir (Etapa C)
            // Não cria nova indicação aqui, só ajusta valores se já existir e estiver pendente
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

        DB::transaction(function () use ($id, $data, $TAB_PEDIDO, $TAB_ITENS, $TAB_ESTOQUE, $TAB_MOV, $TAB_CR, $COL_RESERVA, $COL_DISP) {

            // 1) Lock do pedido
            $pedido = DB::table($TAB_PEDIDO)->lockForUpdate()->where('id', $id)->first();
            if (!$pedido) abort(404, 'Pedido não encontrado.');
            if (mb_strtoupper($pedido->status) === 'CANCELADO') {
                abort(422, 'Este pedido já está cancelado.');
            }

            // 2) Itens do pedido
            $itens = DB::table($TAB_ITENS)->where('pedido_id', $id)->get();

            // 3) Ajustes de estoque (seu código atual aqui...)
            foreach ($itens as $it) {
                $estq = DB::table($TAB_ESTOQUE)
                    ->lockForUpdate()
                    ->where('codfabnumero', $it->codfabnumero)
                    ->first();

                if (!$estq) continue;

                $qtd = (int) $it->quantidade;

                if (in_array(mb_strtoupper($pedido->status), ['PENDENTE', 'ABERTO', 'RESERVADO'])) {

                    $valorReservaAtual = (int) ($estq->{$COL_RESERVA} ?? 0);
                    $novaReserva = max(0, $valorReservaAtual - $qtd);

                    DB::table($TAB_ESTOQUE)
                        ->where('codfabnumero', $it->codfabnumero)
                        ->update([$COL_RESERVA => $novaReserva]);

                    $mov = [
                        'produto_id'   => $it->produto_id,
                        'codfabnumero' => $it->codfabnumero,
                        'tipo_mov'     => 'RESERVA_ENTRADA',
                        'status'       => 'CANCELADO',
                        'origem'       => 'VENDA',
                        'quantidade'   => (int)$qtd,
                        'data_mov'     => now(),
                        'observacao'   => $data['observacao'] . ' (cancelamento de reserva)',
                    ];
                    $this->safeInsertMov($TAB_MOV, $mov, $id);
                } elseif (mb_strtoupper($pedido->status) === 'ENTREGUE') {
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
                        DB::table($TAB_ESTOQUE)
                            ->where('codfabnumero', $it->codfabnumero)
                            ->increment($colBase, $qtd);
                    }

                    $mov = [
                        'produto_id'   => $it->produto_id,
                        'codfabnumero' => $it->codfabnumero,
                        'tipo_mov'     => 'ENTRADA',
                        'status'       => 'CANCELADO',
                        'origem'       => 'VENDA',
                        'quantidade'   => (int)$qtd,
                        'data_mov'     => now(),
                        'observacao'   => $data['observacao'],
                    ];
                    $this->safeInsertMov($TAB_MOV, $mov, $id);
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
                    'valor_premio'  => 0,          // opcional: zerar o prêmio
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
        // normaliza/enforce enums
        if (isset($mov['tipo_mov'])) {
            $allowedTipo = $this->enumValues($table, 'tipo_mov');
            $mov['tipo_mov'] = strtoupper($mov['tipo_mov']);
            if (!in_array($mov['tipo_mov'], $allowedTipo, true)) unset($mov['tipo_mov']);
        }
        if (isset($mov['status'])) {
            $allowedStatus = $this->enumValues($table, 'status');
            $mov['status'] = strtoupper($mov['status']);
            if (!in_array($mov['status'], $allowedStatus, true)) unset($mov['status']);
        }

        // origem_id = pedido_id (quando existir)
        if ($pedidoId !== null) {
            if (Schema::hasColumn($table, 'origem_id'))  $mov['origem_id'] = $pedidoId;
            if (Schema::hasColumn($table, 'pedido_id'))  $mov['pedido_id'] = $pedidoId; // fallback se usa esse nome
        }

        // timestamps, se existirem
        $now = now();
        if (Schema::hasColumn($table, 'created_at') && empty($mov['created_at']))  $mov['created_at'] = $now;
        if (Schema::hasColumn($table, 'updated_at') && empty($mov['updated_at']))  $mov['updated_at'] = $now;

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
        return str_contains($extra, 'GENERATED'); // VIRTUAL/ STORED GENERATED
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
     * Calcula o valor do prêmio de indicação pela faixa de valor do pedido
     */
    private function calcularPremioIndicacao(float $valorPedido): float
    {
        $faixas = [
            [0.00,    99.99,   5.00],
            [100.00,  199.99, 10.00],
            [200.00,  399.99, 20.00],
            [400.00,  599.99, 40.00],
            [600.00,  799.99, 60.00],
            [800.00,  999.99, 80.00],
            [1000.00, 9999.99, 100.00],
        ];

        foreach ($faixas as [$min, $max, $premio]) {
            if ($valorPedido >= $min && $valorPedido <= $max) {
                return $premio;
            }
        }

        return 0.0;
    }

    /**
     * Busca o ID da campanha de indicação (se existir)
     * Regra: campanha ativa cujo tipo tenha descricao = 'Indicação'
     */
    private function getCampanhaIndicacaoId(): ?int
    {
        try {
            return DB::table('appcampanha as c')
                ->join('appcampanha_tipo as t', 't.id', '=', 'c.tipo_id')
                ->where('t.descricao', 'Indicação')
                ->where('c.ativa', 1)
                ->orderByDesc('c.prioridade')
                ->value('c.id');
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar campanha de indicação: ' . $e->getMessage());
            return null;
        }
    }

    // Retorna true se ESTE pedido for a primeira compra concluída de um cliente indicado (indicador_id != 1).
    private function ehPrimeiraCompraIndicada(PedidoVenda $pedido): bool
    {
        $indicadorId = (int) ($pedido->indicador_id ?? 1);

        // Se o indicador for 1, não entra na campanha
        if ($indicadorId === 1) {
            return false;
        }

        // Se já existe registro de indicação pra esse cliente,
        // significa que a 1ª compra já foi processada antes
        $jaTemIndicacao = Indicacao::where('indicado_id', $pedido->cliente_id)->exists();
        if ($jaTemIndicacao) {
            return false;
        }

        // Se já existe outro pedido ENTREGUE desse cliente,
        // também não é mais a primeira compra
        $jaTemPedidoEntregue = PedidoVenda::where('cliente_id', $pedido->cliente_id)
            ->where('status', 'ENTREGUE')
            ->where('id', '!=', $pedido->id)
            ->exists();

        if ($jaTemPedidoEntregue) {
            return false;
        }

        // Chegou aqui: é cliente indicado, sem indicação anterior e sem outra compra entregue
        return true;
    }

    /**
     * Cria/atualiza o registro em appindicacao para este pedido.
     * - Só atua quando indicador_id != 1
     * - Só atua se o pedido estiver ENTREGUE
     * - Se já houver indicação PAGA, não mexe
     * - Se não houver ainda indicação e $criarSeNaoExistir = false, não cria
     */
    private function atualizarIndicacaoParaPedido(PedidoVenda $pedido, bool $criarSeNaoExistir = false): void
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

        // Preenche/atualiza valores
        $indicacao->valor_pedido = $valorPedido;
        $indicacao->valor_premio = $this->calcularPremioIndicacao($valorPedido);

        // Amarra à campanha de indicação, se existir
        if ($campanhaIndicacaoId && !$indicacao->campanha_id) {
            $indicacao->campanha_id = $campanhaIndicacaoId;
        }

        $indicacao->save();
    }

    /**
     * Monta e envia o recibo de entrega via WhatsApp usando BotConversa.
     */
 private function enviarReciboWhatsApp(PedidoVenda $pedido): void
{
    // Garante cliente carregado
    $pedido->loadMissing('cliente');

    $cliente = $pedido->cliente;
    if (!$cliente) {
        return;
    }

    // Ajuste para o campo correto na sua tabela de clientes
    $telefone = $cliente->whatsapp
        ?? $cliente->telefone
        ?? $cliente->celular
        ?? null;

    if (!$telefone) {
        Log::info('Recibo WhatsApp não enviado: cliente sem telefone', [
            'pedido_id'  => $pedido->id,
            'cliente_id' => $cliente->id ?? null,
        ]);
        return;
    }

    $valor   = (float) ($pedido->valor_liquido ?? $pedido->valor_total ?? 0);
    $dataPed = $pedido->data_pedido
        ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y')
        : now()->format('d/m/Y');

    $mensagem = "Olá {$cliente->nome}!\n\n"
        . "Recebemos a confirmação da ENTREGA do seu pedido #{$pedido->id}.\n"
        . "Data do pedido: {$dataPed}\n"
        . "Valor final: R$ " . number_format($valor, 2, ',', '.') . "\n\n";

    if (!empty($pedido->observacao)) {
        $mensagem .= "Obs.: {$pedido->observacao}\n\n";
    }

    $mensagem .= "Qualquer dúvida, estou à disposição 😊";

    // 👉 Aqui agora funciona, porque $this->whatsapp foi injetado no construtor
    $ok = $this->whatsapp->enviarParaTelefone($telefone, $mensagem, $cliente->nome);

    if (!$ok) {
        Log::warning('Falha ao enviar recibo de entrega no WhatsApp', [
            'pedido_id'  => $pedido->id,
            'cliente_id' => $cliente->id ?? null,
            'telefone'   => $telefone,
        ]);
    }
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

        // Se for a primeira compra de cliente indicado,
        // aplica desconto de 5% e marca campanha de indicação
        if ($primeiraCompraIndicada) {

            $valorTotal        = (float) ($pedido->valor_total ?? 0);
            $descontoIndicacao = round($valorTotal * 0.05, 2);

            $descontoAtual     = (float) ($pedido->valor_desconto ?? 0);
            $novoDesconto      = $descontoAtual + $descontoIndicacao;
            $novoValorLiquido  = max(0, $valorTotal - $novoDesconto);

            $pedido->valor_desconto = $novoDesconto;
            $pedido->valor_liquido  = $novoValorLiquido;

            // Observação explicando o desconto da campanha de indicação
            $textoObs = 'Desconto de 5% referente à campanha de indicação.';
            $obsAtual = trim((string) $pedido->observacao);

            if ($obsAtual === '') {
                $pedido->observacao = $textoObs;
            } elseif (!str_contains(mb_strtolower($obsAtual), 'campanha de indicação')) {
                $pedido->observacao = $obsAtual . ' | ' . $textoObs;
            }

            // pegar campanha de indicação
            $campanhaIndicacaoId = $this->getCampanhaIndicacaoId();
            if ($campanhaIndicacaoId && !$pedido->campanha_id) {
                $pedido->campanha_id = $campanhaIndicacaoId;
            }

            $pedido->save();
        }

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

    // 7) 🔔 Envia recibo pelo WhatsApp FORA da transação
    try {
        // garante cliente carregado
        $pedido->loadMissing('cliente');
        $this->enviarReciboWhatsApp($pedido);
    } catch (\Throwable $e) {
        Log::warning('Erro ao enviar recibo WhatsApp após confirmação de entrega', [
            'pedido_id' => $pedido->id,
            'erro'      => $e->getMessage(),
        ]);
    }

    return redirect()->route('vendas.index')
        ->with('success', 'Entrega confirmada, CR gerado, campanhas/indicações processadas e recibo enviado pelo WhatsApp (quando possível).');
}

}
