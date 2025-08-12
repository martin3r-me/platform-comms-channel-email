<?php

namespace Platform\Comms\ChannelEmail;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Platform\Comms\ChannelEmail\Models\CommsChannelEmailAccount;
use Platform\Comms\Registry\ChannelRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Livewire;


class ChannelEmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
         \Platform\Comms\Registry\ChannelRegistry::addRegistrar(
            \Platform\Comms\ChannelEmail\ChannelEmailRegistrar::class
        );

        // --------------------------------------------------
        // Konfigurationsdatei publishen & mergen
        // --------------------------------------------------
        $this->publishes([
            __DIR__ . '/../config/channel-email.php' => config_path('channel-email.php'),
        ], 'channel-email-config');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/channel-email.php',
            'channel-email'
        );

        // --------------------------------------------------
        // Migrations laden & publishen
        // --------------------------------------------------
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'channel-email-migrations');

        // --------------------------------------------------
        // Views laden & publishen
        // --------------------------------------------------
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'comms-channel-email');
        $this->registerLivewireComponents();

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/channel-email'),
        ], 'channel-email-views');

        // --------------------------------------------------
        // Routen laden
        // --------------------------------------------------
        $this->loadRoutesFrom(__DIR__ . '/routes/inbound.php');

        // --------------------------------------------------
        // Middleware registrieren
        // --------------------------------------------------
        $this->app['router']->aliasMiddleware(
            'verify.postmark.basic',
            \Platform\Comms\ChannelEmail\Http\Middleware\VerifyPostmarkBasicAuth::class
        );
    }

    public function register(): void
    {
        
    }

    protected function registerLivewireComponents(): void
    {
        $baseDir = __DIR__ . '/Http/Livewire';
        $baseNamespace = 'Platform\\Comms\\ChannelEmail\\Http\\Livewire';
        $prefix = 'comms-channel-email'; // Modulpräfix

        if (!is_dir($baseDir)) {
            return;
        }

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($rii as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $alias = $prefix . '.' . Str::kebab(str_replace(DIRECTORY_SEPARATOR, '-', pathinfo($relativePath, PATHINFO_DIRNAME) . '/' . $file->getBasename('.php')));
            $alias = rtrim($alias, '-');

            Livewire::component($alias, $class);
        }


    }
}