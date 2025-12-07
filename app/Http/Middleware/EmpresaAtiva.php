<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmpresaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pega o usuário logado a partir do Request
        $user = $request->user();

        if ($user) {
            $empresa = $user->empresa;

            if (!$empresa || !$empresa->ativo) {
                abort(403, 'Sua empresa está inativa ou não está configurada.');
            }

            // Opcional: deixar a empresa disponível globalmente
            app()->instance('empresa', $empresa);
        }

        return $next($request);
    }
}
