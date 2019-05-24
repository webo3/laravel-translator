<?php

namespace webO3\Translator;

use Illuminate\Support\ServiceProvider;
use webO3\Translator\Console\Commands\Export;
use webO3\Translator\Console\Commands\Import;
use webO3\Translator\Console\Commands\Scan;

class TranslatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge configurations
        $this->mergeConfigFrom(
            __DIR__.'/config/webo3-translator.php', 'webo3-translator'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        // Publish config
        $this->publishes([
            __DIR__.'/config/webo3-translator.php' => config_path('webo3-translator.php'),
        ], 'config');

        // Publish commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Export::class,
                Import::class,
                Scan::class,
            ]);
        }
    }
}
