<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Cliente;
use App\Models\PedidoVenda;
use App\Observers\ClienteObserver;
use App\Observers\PedidoObserver;
use App\Observers\PedidoVendaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cliente::observe(ClienteObserver::class);
        PedidoVenda::observe(PedidoVendaObserver::class);
    }
}
