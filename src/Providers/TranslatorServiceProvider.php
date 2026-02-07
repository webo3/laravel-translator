<?php

namespace webO3\Translator\Providers;

use Illuminate\Support\ServiceProvider;
use webO3\Translator\Console\Commands\Export;
use webO3\Translator\Console\Commands\Import;
use webO3\Translator\Console\Commands\Scan;

/**
 * Registers the translator artisan commands and publishes the config file.
 *
 * Commands:
 *   translations:scan    - Scan source files for translation keys
 *   translations:export  - Export translations to CSV
 *   translations:import  - Import translations from CSV
 */
class TranslatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/webo3-translator.php', 'webo3-translator'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/webo3-translator.php' => config_path('webo3-translator.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Export::class,
                Import::class,
                Scan::class,
            ]);
        }
    }
}
