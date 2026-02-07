<?php

namespace webO3\Translator\Tests;

use PHPUnit\Framework\TestCase;

class CsvRoundTripTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/translator-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
    }

    public function testCsvRoundTripWithBom(): void
    {
        $csvFile = $this->tempDir . '/translations.csv';

        $headers = ['key', 'en', 'fr'];
        $rows = [
            ['Hello', '', 'Bonjour'],
            ['Goodbye', '', 'Au revoir'],
            ["It's a test", '', "C'est un test"],
        ];

        // Simulate Export logic (matches Export.php)
        $fp = fopen($csvFile, 'w');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        // Simulate Import logic (matches Import.php)
        $importedHeaders = [];
        $importedRows = [];

        $handle = fopen($csvFile, 'r');
        $bomCheck = fgets($handle, 4);
        if ($bomCheck !== $bom) {
            rewind($handle);
        }

        $lineNum = 0;
        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNum++;
            if ($lineNum === 1) {
                $importedHeaders = $line;
            } else {
                $importedRows[] = $line;
            }
        }
        fclose($handle);

        $this->assertSame($headers, $importedHeaders);
        $this->assertCount(3, $importedRows);
        $this->assertSame("It's a test", $importedRows[2][0]);
        $this->assertSame("C'est un test", $importedRows[2][2]);
    }

    public function testCsvWithUtf8Characters(): void
    {
        $csvFile = $this->tempDir . '/translations.csv';

        $headers = ['key', 'en', 'fr', 'ja'];
        $rows = [
            ['Welcome', 'Welcome', 'Bienvenue', 'ようこそ'],
            ['Thank you', 'Thank you', 'Merci', 'ありがとう'],
        ];

        $fp = fopen($csvFile, 'w');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        $handle = fopen($csvFile, 'r');
        fgets($handle, 4); // skip BOM
        $importedRows = [];
        $lineNum = 0;
        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNum++;
            if ($lineNum > 1) {
                $importedRows[] = $line;
            }
        }
        fclose($handle);

        $this->assertSame('ようこそ', $importedRows[0][3]);
        $this->assertSame('ありがとう', $importedRows[1][3]);
    }

    public function testCsvWithCommasInValues(): void
    {
        $csvFile = $this->tempDir . '/translations.csv';

        $headers = ['key', 'en'];
        $rows = [
            ['Hello, world', 'Hello, world'],
        ];

        $fp = fopen($csvFile, 'w');
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        $handle = fopen($csvFile, 'r');
        fgets($handle, 4); // skip BOM
        $lineNum = 0;
        $importedRows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNum++;
            if ($lineNum > 1) {
                $importedRows[] = $line;
            }
        }
        fclose($handle);

        $this->assertSame('Hello, world', $importedRows[0][0]);
    }
}
