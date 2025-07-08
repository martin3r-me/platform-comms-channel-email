<?php

namespace Martin3r\LaravelActivityLog;

use Illuminate\Support\ServiceProvider;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // (optional später) Migrations aus dem Package laden
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register()
    {
        // Hier kannst du später Configs, Bindings etc. registrieren
    }
}