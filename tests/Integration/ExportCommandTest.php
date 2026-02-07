<?php

namespace webO3\Translator\Tests\Integration;

class ExportCommandTest extends IntegrationTestCase
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

        file_put_contents($langPath . '/en.json', json_encode([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT));

        file_put_contents($langPath . '/fr.json', json_encode([
            'Hello' => 'Bonjour',
            'Goodbye' => 'Au revoir',
        ], JSON_PRETTY_PRINT));
    }

    public function testExportCreatesCsvFile(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $this->assertFileExists(resource_path('lang/translations.csv'));
    }

    public function testExportCsvContainsHeaders(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $csvFile = resource_path('lang/translations.csv');
        $handle = fopen($csvFile, 'r');

        // Skip BOM
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        fclose($handle);

        $this->assertSame('key', $headers[0]);
        $this->assertContains('en', $headers);
        $this->assertContains('fr', $headers);
    }

    public function testExportCsvContainsTranslations(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $csvFile = resource_path('lang/translations.csv');
        $rows = $this->readCsvRows($csvFile);

        // Collect all keys from CSV
        $csvKeys = array_column($rows, 0);

        $this->assertContains('Hello', $csvKeys);
        $this->assertContains('Goodbye', $csvKeys);
    }

    public function testExportShowsTranslatedValueNotKey(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $csvFile = resource_path('lang/translations.csv');
        $handle = fopen($csvFile, 'r');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        $frCol = array_search('fr', $headers);

        $found = false;
        while (($line = fgetcsv($handle)) !== false) {
            if ($line[0] === 'Hello') {
                $this->assertSame('Bonjour', $line[$frCol]);
                $found = true;
                break;
            }
        }
        fclose($handle);

        $this->assertTrue($found, 'Hello key not found in CSV');
    }

    public function testExportHasUtf8Bom(): void
    {
        $this->artisan('translations:export')
            ->assertSuccessful();

        $csvFile = resource_path('lang/translations.csv');
        $raw = file_get_contents($csvFile);
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        $this->assertStringStartsWith($bom, $raw);
    }

    private function readCsvRows(string $csvFile): array
    {
        $handle = fopen($csvFile, 'r');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $rows = [];
        $lineNum = 0;
        while (($line = fgetcsv($handle)) !== false) {
            $lineNum++;
            if ($lineNum > 1) { // skip header
                $rows[] = $line;
            }
        }
        fclose($handle);

        return $rows;
    }
}
