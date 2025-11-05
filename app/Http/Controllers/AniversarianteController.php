<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AniversarianteController extends Controller
{
    /**
     * Ajuste aqui o nome da coluna de nascimento da sua tabela de clientes.
     * Exemplos comuns: 'data_nascimento', 'nascimento', 'dt_nascimento'
     */
    private const COL_NASCIMENTO = 'data_nascimento';

    /**
     * (Opcional) View HTML tradicional já usada pela sua rota existente /aniversariantes/{mes}
     * Se você já tem uma view específica, mantenha. Aqui deixo um básico.
     */
    public function listar($mes)
    {
        $mes = (int) $mes;
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        $col = self::COL_NASCIMENTO;

        $clientes = Cliente::query()
            ->whereMonth($col, $mes)
            ->orderByRaw("DAY($col)")
            ->get();

        // Se você já tem uma view, pode trocar 'aniversariantes.index' por ela
        if (view()->exists('aniversariantes.index')) {
            return view('aniversariantes.index', compact('clientes', 'mes'));
        }

        // Fallback simples (sem view)
        return response()->json([
            'mes'      => $mes,
            'total'    => $clientes->count(),
            'clientes' => $clientes,
        ]);
    }

    /**
     * Endpoint JSON para o painel lateral (off-canvas).
     * Retorna: id, nome, dia/mes, idade, telefone, nascimento (ISO), iniciais
     */
    public function listarJson($mes)
    {
        $mes = (int) $mes;
        if ($mes < 1 || $mes > 12) {
            return response()->json(['data' => []]);
        }

        $col = self::COL_NASCIMENTO;

        // Se a coluna não existir, evite erro silencioso:
        if (! Schema::hasColumn((new Cliente())->getTable(), $col)) {
            return response()->json([
                'data'  => [],
                'error' => "Coluna '{$col}' não encontrada em clientes. Ajuste COL_NASCIMENTO no controller."
            ], 200);
        }

        $clientes = Cliente::query()
            ->whereMonth($col, $mes)
            ->orderByRaw("DAY($col)")
            ->get(['id', 'nome', $col, 'telefone']);

        $data = $clientes->map(function ($c) use ($col) {
            // Trata nulos/formatos inválidos
            if (empty($c->{$col})) {
                return [
                    'id'         => $c->id,
                    'nome'       => $c->nome,
                    'dia_mes'    => '-',
                    'idade'      => null,
                    'telefone'   => $c->telefone,
                    'nascimento' => null,
                    'iniciais'   => mb_strtoupper(mb_substr($c->nome ?? '', 0, 1)),
                ];
            }

            $nasc = Carbon::parse($c->{$col});

            return [
                'id'         => $c->id,
                'nome'       => $c->nome,
                'dia_mes'    => $nasc->format('d/m'),
                'idade'      => $nasc->age,
                'telefone'   => $c->telefone,
                'nascimento' => $nasc->toDateString(),
                'iniciais'   => mb_strtoupper(mb_substr($c->nome ?? '', 0, 1)),
            ];
        })->values();

        return response()->json(['data' => $data], 200);
    }
}
