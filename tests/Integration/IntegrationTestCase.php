<?php

namespace webO3\Translator\Tests\Integration;

use Orchestra\Testbench\TestCase;
use webO3\Translator\Providers\TranslatorServiceProvider;

abstract class IntegrationTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TranslatorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('webo3-translator.languages', ['en', 'fr']);
        $app['config']->set('webo3-translator.lang_path', resource_path('lang'));
        $app['config']->set('webo3-translator.export_path', null);
        $app['config']->set('webo3-translator.scan_paths', [resource_path()]);
        $app['config']->set('webo3-translator.format', 'csv');
    }

    /**
     * Ensure the lang directory exists inside the test app.
     */
    protected function ensureLangDirectory(): string
    {
        $langPath = resource_path('lang');
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }

        return $langPath;
    }

    /**
     * Clean up generated files after each test.
     */
    protected function tearDown(): void
    {
        $langPath = resource_path('lang');
        if (is_dir($langPath)) {
            foreach (glob($langPath . '/*') as $file) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
