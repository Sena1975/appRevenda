<?php

/**
 * Bootstrap compatível com LOCAL e HOSTNET.
 * - Se existir o caminho absoluto de produção, usa ele.
 * - Caso contrário, usa os caminhos relativos padrão (ambiente local).
 */

$prodBase = '/home/senadevtech/apprevenda';

if (is_dir($prodBase) && file_exists($prodBase . '/vendor/autoload.php')) {
    // Produção (Hostnet) - caminhos ABSOLUTOS
    require $prodBase . '/vendor/autoload.php';
    $app = require_once $prodBase . '/bootstrap/app.php';
} else {
    // Desenvolvimento LOCAL - caminhos RELATIVOS padrão Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
