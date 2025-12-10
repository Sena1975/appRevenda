<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampanhaRequest;
use App\Models\Campanha;
use App\Models\CampanhaTipo;
use App\Models\MensagemModelo;
use App\Models\CampanhaMensagem;
use App\Models\Produto;
use App\Support\CampanhaEventos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampanhaController extends Controller
{
    /**
     * Descobre o ID da empresa atual (usuário logado ou middleware EmpresaAtiva).
     */
    private function getEmpresaId(): int
    {
        $user    = Auth::user();
        $empresa = $user?->empresa;

        if (!$empresa && app()->bound('empresa')) {
            $empresa = app('empresa');
        }

        if (!$empresa) {
            abort(500, 'Empresa não definida para o usuário atual.');
        }

        return (int) $empresa->id;
    }

    public function index()
    {
        $empresaId = $this->getEmpresaId();

        $campanhas = Campanha::with('tipo')
            ->daEmpresa($empresaId)
            ->orderBy('prioridade')
            ->paginate(20);

        return view('campanhas.index', compact('campanhas'));
    }

    public function create()
    {
        $tipos    = CampanhaTipo::orderBy('descricao')->get();
        $produtos = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']); // para eventual brinde

        return view('campanhas.create', compact('tipos', 'produtos'));
    }

    public function edit(Campanha $campanha)
    {
        $empresaId = $this->getEmpresaId();

        // Segurança multi-empresa
        if ((int) $campanha->empresa_id !== $empresaId) {
            abort(403, 'Você não tem permissão para editar esta campanha.');
        }

        // Tipos e produtos (mesmo que na create)
        $tipos    = CampanhaTipo::orderBy('descricao')->get();
        $produtos = Produto::orderBy('nome')->get(['id', 'nome', 'codfabnumero']);

        // Modelos de mensagem ativos (WhatsApp, por enquanto)
        $modelosMensagem = MensagemModelo::where('ativo', true)
            ->where('canal', 'whatsapp')
            ->orderBy('nome')
            ->get();

        // Configurações já salvas para esta campanha
        $mensagensConfiguradas = $campanha->mensagensConfiguradas
            ->keyBy('evento'); // vira um map: [evento => CampanhaMensagem]

        // Por enquanto vamos tratar eventos só para campanhas de indicação
        $eventosIndicacao = [];
        if ($campanha->metodo_php === 'isCampanhaIndicacao') {
            $eventosIndicacao = CampanhaEventos::eventosIndicacao();
        }

        return view('campanhas.edit', [
            'campanha'              => $campanha,
            'tipos'                 => $tipos,
            'produtos'              => $produtos,
            'modelosMensagem'       => $modelosMensagem,
            'mensagensConfiguradas' => $mensagensConfiguradas,
            'eventosIndicacao'      => $eventosIndicacao,
        ]);
    }

    public function show(Campanha $campanha)
    {
        // Carrega tipo e mensagens configuradas com os modelos
        $campanha->load([
            'tipo',
            'mensagensConfiguradas.modelo',
        ]);

        // Labels dos eventos (ex.: para campanhas de indicação)
        $eventosIndicacao = [];
        if ($campanha->metodo_php === 'isCampanhaIndicacao') {
            $eventosIndicacao = CampanhaEventos::eventosIndicacao();
        }

        return view('campanhas.show', [
            'campanha'        => $campanha,
            'eventosIndicacao' => $eventosIndicacao,
        ]);
    }

    public function store(CampanhaRequest $request)
    {
        $empresaId = $this->getEmpresaId();

        $dados = $request->validated();

        // Sempre vincula à empresa atual
        $dados['empresa_id'] = $empresaId;

        $dados['ativa']                      = $request->boolean('ativa');
        $dados['cumulativa']                 = $request->boolean('cumulativa');
        $dados['aplicacao_automatica']       = $request->boolean('aplicacao_automatica');
        $dados['acumulativa_por_valor']      = $request->boolean('acumulativa_por_valor');
        $dados['acumulativa_por_quantidade'] = $request->boolean('acumulativa_por_quantidade');

        // Ajuste de campos conforme tipo
        if ((int) $dados['tipo_id'] === 1) {
            // Cupom por valor
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']         = 'valor';
        } elseif ((int) $dados['tipo_id'] === 2) {
            // Cupom por quantidade
            $dados['valor_base_cupom']        = null;
            $dados['tipo_acumulacao']         = 'quantidade';
        } else {
            // Brinde ou outros tipos
            $dados['valor_base_cupom']        = null;
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']         = null;
        }

        $campanha = Campanha::create($dados);

        return redirect()
            ->route('campanhas.restricoes', $campanha->id)
            ->with('ok', "Campanha #{$campanha->id} criada com sucesso! Defina as restrições.");
    }

    public function update(CampanhaRequest $request, Campanha $campanha)
    {
        $empresaId = $this->getEmpresaId();

        // Segurança multi-empresa
        if ((int) $campanha->empresa_id !== $empresaId) {
            abort(403, 'Você não tem permissão para editar esta campanha.');
        }

        $dados = $request->validated();

        // Empresa não muda na edição (garantia)
        $dados['empresa_id'] = $empresaId;

        $dados['ativa']                      = $request->boolean('ativa');
        $dados['cumulativa']                 = $request->boolean('cumulativa');
        $dados['aplicacao_automatica']       = $request->boolean('aplicacao_automatica');
        $dados['acumulativa_por_valor']      = $request->boolean('acumulativa_por_valor');
        $dados['acumulativa_por_quantidade'] = $request->boolean('acumulativa_por_quantidade');

        // Ajuste de campos conforme tipo
        if ((int) $dados['tipo_id'] === 1) {
            // Cupom por valor
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']         = 'valor';
        } elseif ((int) $dados['tipo_id'] === 2) {
            // Cupom por quantidade
            $dados['valor_base_cupom']        = null;
            $dados['tipo_acumulacao']         = 'quantidade';
        } else {
            // Brinde ou outros tipos
            $dados['valor_base_cupom']        = null;
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']         = null;
        }

        $campanha->update($dados);

        // === Configuração de mensagens da campanha ===
        if ($campanha->metodo_php === 'isCampanhaIndicacao') {

            $eventosIndicacao = CampanhaEventos::eventosIndicacao();

            // Arrays vindos do form (name="mensagem_modelo_id[{{ $evento }}]" etc.)
            $inputModelos = $request->input('mensagem_modelo_id', []); // [evento => modelo_id]
            $inputDelay   = $request->input('delay_minutos', []);      // [evento => delay]
            $inputAtivo   = $request->input('ativo_msg', []);          // [evento => '1'|'0']

            foreach ($eventosIndicacao as $evento => $descricao) {

                $modeloId = $inputModelos[$evento] ?? null;

                // Se não tem modelo selecionado para este evento, removemos a config (se existir)
                if (empty($modeloId)) {
                    CampanhaMensagem::where('campanha_id', $campanha->id)
                        ->where('evento', $evento)
                        ->delete();
                    continue;
                }

                $delay = isset($inputDelay[$evento]) && $inputDelay[$evento] !== ''
                    ? (int) $inputDelay[$evento]
                    : null;

                $ativo = isset($inputAtivo[$evento]) && $inputAtivo[$evento] ? true : false;

                CampanhaMensagem::updateOrCreate(
                    [
                        'campanha_id' => $campanha->id,
                        'evento'      => $evento,
                    ],
                    [
                        'mensagem_modelo_id' => $modeloId,
                        'delay_minutos'      => $delay,
                        'ativo'              => $ativo,
                    ]
                );
            }
        }

        return redirect()
            ->route('campanhas.edit', $campanha->id)
            ->with('ok', "Campanha #{$campanha->id} atualizada com sucesso!");
    }
    /**
     * Salva as configurações de mensagens da campanha (tabela appcampanha_mensagem).
     * 
     * Espera no Request:
     * - mensagem_modelo_id[evento] => id do modelo
     * - delay_minutos[evento]      => inteiro
     * - ativo_msg[evento]          => checkbox
     */

    private function salvarMensagensCampanha(Request $request, Campanha $campanha): void
    {
        // Arrays vindos do formulário
        $mensagensPorEvento = $request->input('mensagem_modelo_id', []);
        $delays             = $request->input('delay_minutos', []);
        $ativos             = $request->input('ativo_msg', []);

        // Por enquanto, só tratamos campanhas de indicação
        if ($campanha->metodo_php === 'isCampanhaIndicacao') {
            $eventos = CampanhaEventos::eventosIndicacao();
        } else {
            $eventos = [];
        }

        foreach ($eventos as $evento => $meta) {

            $modeloId = $mensagensPorEvento[$evento] ?? null;
            $delay    = isset($delays[$evento]) ? (int) $delays[$evento] : 0;
            $ativo    = isset($ativos[$evento]) ? 1 : 0;

            // Se nenhum modelo foi escolhido, removemos qualquer configuração anterior
            if (!$modeloId) {
                $campanha->mensagensConfiguradas()
                    ->where('evento', $evento)
                    ->delete();

                continue;
            }

            CampanhaMensagem::updateOrCreate(
                [
                    'campanha_id' => $campanha->id,
                    'evento'      => $evento,
                ],
                [
                    'mensagem_modelo_id' => $modeloId,
                    'delay_minutos'      => $delay,
                    'ativo'              => $ativo,
                    'canal'              => 'whatsapp',
                ],
            );
        }
    }
}
