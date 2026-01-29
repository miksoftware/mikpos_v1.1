<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forzar HTTPS siempre
        URL::forceScheme('https');
        
        // Confiar en todos los proxies (Traefik)
        $this->app['request']->server->set('HTTPS', 'on');
    }
}
