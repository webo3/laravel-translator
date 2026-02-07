<?php

namespace webO3\Translator\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use webO3\Translator\Formatters\CsvFormatter;

class CsvFormatterTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/csv-formatter-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testExportCreatesCsvWithBomAndHeaders(): void
    {
        $formatter = new CsvFormatter();
        $files = $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Bonjour']],
            $this->tempDir
        );

        $this->assertCount(1, $files);
        $this->assertFileExists($files[0]);

        $raw = file_get_contents($files[0]);
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $this->assertStringStartsWith($bom, $raw);

        // Check headers
        $handle = fopen($files[0], 'r');
        fgets($handle, 4); // skip BOM
        $headers = fgetcsv($handle);
        fclose($handle);

        $this->assertSame(['key', 'en', 'fr'], $headers);
    }

    public function testExportLeavesUntranslatedCellsEmpty(): void
    {
        $formatter = new CsvFormatter();
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Hello']],
            $this->tempDir
        );

        $rows = $this->readCsvData($this->tempDir . '/translations.csv');
        // Both en and fr have "Hello" as value (same as key), so both columns should be empty
        $this->assertSame(['Hello', '', ''], $rows[0]);
    }

    public function testExportIncludesAllKeysAcrossLanguages(): void
    {
        $formatter = new CsvFormatter();
        $formatter->export(
            ['en', 'fr'],
            [
                'en' => ['Hello' => 'Hello', 'World' => 'World'],
                'fr' => ['Hello' => 'Bonjour'],
            ],
            $this->tempDir
        );

        $rows = $this->readCsvData($this->tempDir . '/translations.csv');
        $keys = array_column($rows, 0);

        $this->assertContains('Hello', $keys);
        $this->assertContains('World', $keys);
    }

    public function testImportReturnsTranslationsByLanguage(): void
    {
        $formatter = new CsvFormatter();

        // First export some data
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Bonjour']],
            $this->tempDir
        );

        // Then import
        $result = $formatter->import($this->tempDir);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('fr', $result);
        $this->assertSame('Bonjour', $result['fr']['Hello']);
    }

    public function testRoundTrip(): void
    {
        $formatter = new CsvFormatter();

        $original = [
            'en' => ['Hello' => 'Hello', 'Goodbye' => 'Goodbye'],
            'fr' => ['Hello' => 'Bonjour', 'Goodbye' => 'Au revoir'],
        ];

        $formatter->export(['en', 'fr'], $original, $this->tempDir);
        $imported = $formatter->import($this->tempDir);

        // Untranslated values (value == key) are exported as empty, imported back as empty string
        // So en values become empty strings since they equal the key
        $this->assertSame('Bonjour', $imported['fr']['Hello']);
        $this->assertSame('Au revoir', $imported['fr']['Goodbye']);
    }

    private function readCsvData(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $rows = [];
        $lineNum = 0;
        while (($line = fgetcsv($handle)) !== false) {
            $lineNum++;
            if ($lineNum > 1) {
                $rows[] = $line;
            }
        }
        fclose($handle);

        return $rows;
    }
}
