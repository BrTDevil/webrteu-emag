<?php

declare(strict_types=1);

namespace WeBRTeu\Emag;

use Illuminate\Support\ServiceProvider;
use WeBRTeu\Emag\Commands\ImportCommand;

class EmagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/emag.php', 'emag');

        $this->app->singleton(EmagClient::class);
        $this->app->singleton(EmagCatalog::class);
        $this->app->singleton(EmagImporter::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/emag.php' => $this->app->configPath('emag.php'),
        ], 'emag-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_emag_tables.php.stub' => $this->app->databasePath('migrations/'.date('Y_m_d_His').'_create_emag_tables.php'),
        ], 'emag-migrations');

        $this->commands([ImportCommand::class]);
    }
}
