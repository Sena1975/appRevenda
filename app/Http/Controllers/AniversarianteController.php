<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AniversarianteController extends Controller
{
    public function listar($mes)
    {
        $aniversariantes = DB::table('appcliente')
            ->select('id', 'nome', 'data_nascimento')
            ->whereMonth('data_nascimento', $mes)
            ->orderByRaw('DAY(data_nascimento)')
            ->get()
            ->map(function ($a) {
                $data = Carbon::parse($a->data_nascimento);
                return [
                    'id' => $a->id,
                    'nome' => $a->nome,
                    'data_formatada' => $data->format('d/m'),
                    'idade' => $data->age,
                ];
            });

        return response()->json($aniversariantes);
    }
}
