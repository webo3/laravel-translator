<?php

namespace webO3\Translator\Tests\Integration;

class XliffRoundTripTest extends IntegrationTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('webo3-translator.format', 'xliff');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureLangDirectory();
        $this->seedLanguageFiles();
    }

    private function seedLanguageFiles(): void
    {
        $langPath = resource_path('lang');

        file_put_contents($langPath . '/en.json', json_encode([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT));

        file_put_contents($langPath . '/fr.json', json_encode([
            'Hello' => 'Bonjour',
            'Goodbye' => 'Au revoir',
        ], JSON_PRETTY_PRINT));
    }

    public function testExportCreatesXliffFile(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $this->assertFileExists(resource_path('lang/translations-fr.xlf'));
    }

    public function testExportDoesNotCreateFileForSourceLanguage(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $this->assertFileDoesNotExist(resource_path('lang/translations-en.xlf'));
    }

    public function testFullScanExportImportRoundTrip(): void
    {
        $langPath = resource_path('lang');

        // Create a source file to scan
        $viewsPath = resource_path('views');
        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }
        file_put_contents($viewsPath . '/test.php', "<?php echo __('Round trip key');");

        // Step 1: Scan
        $this->artisan('translations:scan')->assertSuccessful();

        $enData = json_decode(file_get_contents($langPath . '/en.json'), true);
        $this->assertArrayHasKey('Round trip key', $enData);

        // Step 2: Simulate translation in fr.json
        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);
        $frData['Round trip key'] = 'Clef aller-retour';
        file_put_contents($langPath . '/fr.json', json_encode($frData, JSON_PRETTY_PRINT));

        // Step 3: Export to XLIFF
        $this->artisan('translations:export')->assertSuccessful();
        $this->assertFileExists($langPath . '/translations-fr.xlf');

        // Step 4: Reset fr.json to simulate getting the XLIFF back from translator
        $frData['Round trip key'] = 'Round trip key';
        file_put_contents($langPath . '/fr.json', json_encode($frData, JSON_PRETTY_PRINT));

        // Step 5: Import from XLIFF
        $this->artisan('translations:import')->assertSuccessful();

        // Verify the translation was restored
        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);
        $this->assertSame('Clef aller-retour', $frData['Round trip key']);

        @unlink($viewsPath . '/test.php');
    }
}
