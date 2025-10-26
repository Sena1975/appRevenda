<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalizacaoController extends Controller
{
    // Retorna cidades com base no UF selecionado
    public function getCidades($uf_id)
    {
        $cidades = DB::table('appcidade')
            ->where('uf_id', $uf_id)
            ->orderBy('nome')
            ->get(['id', 'nome']);
        return response()->json($cidades);
    }

    // Retorna bairros com base na cidade selecionada
    public function getBairros($cidade_id)
    {
        $bairros = DB::table('appbairro')
            ->where('cidade_id', $cidade_id)
            ->orderBy('nome')
            ->get(['id', 'nome']);
        return response()->json($bairros);
    }
}
