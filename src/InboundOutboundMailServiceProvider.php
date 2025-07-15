<?php

namespace Martin3r\LaravelInboundOutboundMail;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InboundOutboundMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Config publishen
        $this->publishes([
            __DIR__ . '/../config/inbound-outbound-mail.php' => config_path('inbound-outbound-mail.php'),
        ], 'config');

        // Migrationen laden & publishen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Views laden & publishen
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-inbound-outbound-mail');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-inbound-outbound-mail'),
        ], 'views');

        // Livewire-Komponente registrieren
        if (class_exists(Livewire::class)) {
            
        }
    }

    public function register(): void
    {
        // Config-Merge
        $this->mergeConfigFrom(
            __DIR__ . '/../config/inbound-outbound-mail.php',
            'inbound-outbound-mail'
        );
    }
}
