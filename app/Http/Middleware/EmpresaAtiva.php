<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaAtiva
{
    public function handle(Request $request, Closure $next)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->empresa_id) {
            abort(403, 'Sua empresa não está configurada.');
        }

        $empresa = Empresa::where('id', $usuario->empresa_id)
            ->where('ativo', 1)
            ->first();

        if (!$empresa) {
            abort(403, 'Sua empresa está inativa ou não está configurada.');
        }

        // opcional: deixar acessível globalmente
        app()->instance('empresa', $empresa);

        return $next($request);
    }
}
