<?php

namespace webO3\Translator\Tests\Integration;

class ScanCommandTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureLangDirectory();
        $this->createSourceFiles();
    }

    private function createSourceFiles(): void
    {
        // Create a PHP file with translations in the resources directory
        $viewsPath = resource_path('views');
        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }

        file_put_contents($viewsPath . '/welcome.php', implode("\n", [
            '<?php',
            "echo __('Hello world');",
            "echo __('Welcome :name', ['name' => 'John']);",
            "echo Lang::get('messages.greeting');",
            "@lang('Login')",
        ]));

        file_put_contents($viewsPath . '/app.js', implode("\n", [
            "\$t('Vue key');",
            "__('Shared key');",
        ]));
    }

    public function testScanCreatesLanguageFiles(): void
    {
        $this->artisan('translations:scan')
            ->assertSuccessful();

        $this->assertFileExists(resource_path('lang/en.json'));
        $this->assertFileExists(resource_path('lang/fr.json'));
    }

    public function testScanExtractsKeys(): void
    {
        $this->artisan('translations:scan')
            ->assertSuccessful();

        $enData = json_decode(file_get_contents(resource_path('lang/en.json')), true);

        $this->assertArrayHasKey('Hello world', $enData);
        $this->assertArrayHasKey('Welcome :name', $enData);
        $this->assertArrayHasKey('messages.greeting', $enData);
        $this->assertArrayHasKey('Login', $enData);
        $this->assertArrayHasKey('Shared key', $enData);
    }

    public function testScanDefaultsKeyValueToKeyItself(): void
    {
        $this->artisan('translations:scan')
            ->assertSuccessful();

        $enData = json_decode(file_get_contents(resource_path('lang/en.json')), true);

        $this->assertSame('Hello world', $enData['Hello world']);
    }

    public function testScanPreservesExistingTranslations(): void
    {
        // Pre-populate with an existing translation
        $langPath = $this->ensureLangDirectory();
        file_put_contents($langPath . '/en.json', json_encode([
            'Hello world' => 'Hi there!',
        ]));

        $this->artisan('translations:scan')
            ->assertSuccessful();

        $enData = json_decode(file_get_contents($langPath . '/en.json'), true);

        // Existing translation should be preserved
        $this->assertSame('Hi there!', $enData['Hello world']);
        // New keys should be added
        $this->assertArrayHasKey('Welcome :name', $enData);
    }

    public function testScanOutputsSortedJson(): void
    {
        $this->artisan('translations:scan')
            ->assertSuccessful();

        $enData = json_decode(file_get_contents(resource_path('lang/en.json')), true);
        $keys = array_keys($enData);
        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys);
    }

    protected function tearDown(): void
    {
        // Clean up only the files we created
        $viewsPath = resource_path('views');
        @unlink($viewsPath . '/welcome.php');
        @unlink($viewsPath . '/app.js');

        parent::tearDown();
    }
}
