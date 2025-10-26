<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocalizacaoController extends Controller
{
    public function getCidades($uf_id)
    {
        return DB::table('appcidade')
            ->where('uf_id', $uf_id)
            ->orderBy('nome')
            ->get();
    }

    public function getBairros($cidade_id)
    {
        return DB::table('appbairro')
            ->where('cidade_id', $cidade_id)
            ->orderBy('nome')
            ->get();
    }

    // Busca cidade/UF pelo nome (usada no CEP automático)
    public function getLocalizacao(Request $request)
    {
        $uf = DB::table('appuf')->where('sigla', $request->uf)->first();
        $cidade = DB::table('appcidade')
            ->where('nome', 'like', $request->cidade)
            ->where('uf_id', $uf->id ?? null)
            ->first();

        return response()->json([
            'uf_id' => $uf->id ?? null,
            'cidade_id' => $cidade->id ?? null,
        ]);
    }
}
