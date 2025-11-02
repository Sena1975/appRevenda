<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampanhaRequest;
use App\Models\Campanha;
use App\Models\CampanhaTipo;
use App\Models\Produto;
use Illuminate\Http\Request;

class CampanhaController extends Controller
{
    public function index()
    {
        $campanhas = Campanha::with('tipo')->orderBy('prioridade')->paginate(20);
        return view('campanhas.index', compact('campanhas')); // opcional agora
    }

    public function create()
    {
        $tipos = CampanhaTipo::orderBy('descricao')->get();
        $produtos = Produto::orderBy('nome')->get(['id','nome','codfabnumero']); // para eventual brinde
        return view('campanhas.create', compact('tipos','produtos'));
    }

    public function store(CampanhaRequest $request)
    {
        $dados = $request->validated();

        // Ajuste de campos que podem vir vazios a depender do tipo
        if ((int)$dados['tipo_id'] === 1) {
            // Cupom por valor
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao'] = 'valor';
        } elseif ((int)$dados['tipo_id'] === 2) {
            // Cupom por quantidade
            $dados['valor_base_cupom'] = null;
            $dados['tipo_acumulacao'] = 'quantidade';
        } else {
            // Brinde
            $dados['valor_base_cupom'] = null;
            $dados['quantidade_minima_cupom'] = null;
            $dados['tipo_acumulacao'] = null;
        }

        // timestamps customizados são tratados pelo Model (CRiado/ATualizado)
        $campanha = Campanha::create($dados);

        return redirect()
            ->route('campanhas.restricoes', $campanha->id)
            ->with('ok', "Campanha #{$campanha->id} criada com sucesso! Defina as restrições.");
    }
}
