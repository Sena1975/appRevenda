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
            ->daEmpresa($empresaId)         // 👈 FILTRO POR EMPRESA
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
            // ... demais dados que você já envia ...
            'modelosMensagem'       => $modelosMensagem,
            'mensagensConfiguradas' => $mensagensConfiguradas,
            'eventosIndicacao'      => $eventosIndicacao,
        ]);
    }

    public function store(CampanhaRequest $request)
    {
        $empresaId = $this->getEmpresaId();

        $dados = $request->validated();

        // Sempre vincula à empresa atual
        $dados['empresa_id'] = $empresaId;

        $dados['ativa']                       = $request->boolean('ativa');
        $dados['cumulativa']                  = $request->boolean('cumulativa');
        $dados['aplicacao_automatica']        = $request->boolean('aplicacao_automatica');
        $dados['acumulativa_por_valor']       = $request->boolean('acumulativa_por_valor');
        $dados['acumulativa_por_quantidade']  = $request->boolean('acumulativa_por_quantidade');

        // Ajuste de campos conforme tipo
        if ((int) $dados['tipo_id'] === 1) {
            // Cupom por valor
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']        = 'valor';
        } elseif ((int) $dados['tipo_id'] === 2) {
            // Cupom por quantidade
            $dados['valor_base_cupom']       = null;
            $dados['tipo_acumulacao']        = 'quantidade';
        } else {
            // Brinde
            $dados['valor_base_cupom']        = null;
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao']         = null;
        }

        $campanha = Campanha::create($dados);

        return redirect()
            ->route('campanhas.restricoes', $campanha->id)
            ->with('ok', "Campanha #{$campanha->id} criada com sucesso! Defina as restrições.");
    }
}
