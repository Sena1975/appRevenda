<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EmpresaAtiva;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // se já tiver outras configs, mantenha

        // aliases de middleware (por nome)
        $middleware->alias([
            'empresa.ativa' => EmpresaAtiva::class, // <<< AQUI O ALIAS
            // ... outros aliases que você tiver
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
