<?php

namespace webO3\Translator\Tests\Integration;

class ServiceProviderTest extends IntegrationTestCase
{
    public function testConfigIsLoaded(): void
    {
        $languages = config('webo3-translator.languages');
        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
    }

    public function testScanCommandIsRegistered(): void
    {
        $this->assertTrue($this->app->make('Illuminate\Contracts\Console\Kernel')->all()['translations:scan'] !== null);
    }

    public function testExportCommandIsRegistered(): void
    {
        $this->assertTrue($this->app->make('Illuminate\Contracts\Console\Kernel')->all()['translations:export'] !== null);
    }

    public function testImportCommandIsRegistered(): void
    {
        $this->assertTrue($this->app->make('Illuminate\Contracts\Console\Kernel')->all()['translations:import'] !== null);
    }
}
