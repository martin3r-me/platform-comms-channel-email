<?php

namespace Martin3r\LaravelActivityLog;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Views aus eurem Package laden & publishen
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-activity-log');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-activity-log'),
        ], 'views');

        // Livewire-Komponente im Unterordner Activities registrieren
        if (class_exists(Livewire::class)) {
            Livewire::component(
                'activities-index',
                \Martin3r\LaravelActivityLog\Http\Livewire\Activities\Index::class
            );
        }
    }

    public function register()
    {
        // Hier könnt ihr später Configs, Bindings etc. registrieren
    }
}