<?php

namespace Martin3r\LaravelActivityLog;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Config publishen
        $this->publishes([
            __DIR__ . '/../config/activity-log.php' => config_path('activity-log.php'),
        ], 'config');

        // Migrationen laden & publishen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Views laden & publishen
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-activity-log');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-activity-log'),
        ], 'views');

        // Livewire-Komponente registrieren
        if (class_exists(Livewire::class)) {
            Livewire::component(
                'activities-index',
                \Martin3r\LaravelActivityLog\Http\Livewire\Activities\Index::class
            );
        }
    }

    public function register(): void
    {
        // Config-Merge
        $this->mergeConfigFrom(
            __DIR__ . '/../config/activity-log.php',
            'activity-log'
        );
    }
}
