<?php

namespace webO3\Translator\Tests\Integration;

class ImportCommandTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureLangDirectory();
        $this->seedLanguageFiles();
    }

    private function seedLanguageFiles(): void
    {
        $langPath = resource_path('lang');

        // Create initial JSON files with untranslated keys
        file_put_contents($langPath . '/en.json', json_encode([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT));

        file_put_contents($langPath . '/fr.json', json_encode([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT));

        // Create a CSV with translations
        $csvFile = $langPath . '/translations.csv';
        $fp = fopen($csvFile, 'w');

        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, ['key', 'en', 'fr']);
        fputcsv($fp, ['Hello', 'Hello', 'Bonjour']);
        fputcsv($fp, ['Goodbye', 'Goodbye', 'Au revoir']);

        fclose($fp);
    }

    public function testImportUpdatesJsonFiles(): void
    {
        $this->artisan('translations:import')
            ->assertSuccessful();

        $frData = json_decode(file_get_contents(resource_path('lang/fr.json')), true);

        $this->assertSame('Bonjour', $frData['Hello']);
        $this->assertSame('Au revoir', $frData['Goodbye']);
    }

    public function testImportPreservesExistingKeys(): void
    {
        // Add an extra key that is not in the CSV
        $langPath = resource_path('lang');
        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);
        $frData['Extra key'] = 'Clef extra';
        file_put_contents($langPath . '/fr.json', json_encode($frData, JSON_PRETTY_PRINT));

        $this->artisan('translations:import')
            ->assertSuccessful();

        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);

        $this->assertSame('Clef extra', $frData['Extra key']);
        $this->assertSame('Bonjour', $frData['Hello']);
    }

    public function testImportSortsKeysAlphabetically(): void
    {
        $this->artisan('translations:import')
            ->assertSuccessful();

        $frData = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        $keys = array_keys($frData);
        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys);
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

        // Verify key was created in both languages
        $enData = json_decode(file_get_contents($langPath . '/en.json'), true);
        $this->assertArrayHasKey('Round trip key', $enData);

        // Step 2: Simulate translation in fr.json
        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);
        $frData['Round trip key'] = 'Clef aller-retour';
        file_put_contents($langPath . '/fr.json', json_encode($frData, JSON_PRETTY_PRINT));

        // Step 3: Export
        $this->artisan('translations:export')->assertSuccessful();
        $this->assertFileExists($langPath . '/translations.csv');

        // Step 4: Reset fr.json to simulate getting the CSV back from translator
        $frData['Round trip key'] = 'Round trip key';
        file_put_contents($langPath . '/fr.json', json_encode($frData, JSON_PRETTY_PRINT));

        // Step 5: Import
        $this->artisan('translations:import')->assertSuccessful();

        // Verify the translation was restored from CSV
        $frData = json_decode(file_get_contents($langPath . '/fr.json'), true);
        $this->assertSame('Clef aller-retour', $frData['Round trip key']);

        // Clean up the test file we created
        @unlink($viewsPath . '/test.php');
    }
}
