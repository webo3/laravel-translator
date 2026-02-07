<?php

namespace webO3\Translator\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use webO3\Translator\Formatters\XliffFormatter;

class XliffFormatterTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/xliff-formatter-test-' . uniqid();
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

    public function testExportCreatesOneFilePerTargetLanguage(): void
    {
        $formatter = new XliffFormatter();
        $files = $formatter->export(
            ['en', 'fr', 'de'],
            [
                'en' => ['Hello' => 'Hello'],
                'fr' => ['Hello' => 'Bonjour'],
                'de' => ['Hello' => 'Hallo'],
            ],
            $this->tempDir
        );

        // Source language (en) should not get a file
        $this->assertCount(2, $files);
        $this->assertFileExists($this->tempDir . '/translations-fr.xlf');
        $this->assertFileExists($this->tempDir . '/translations-de.xlf');
    }

    public function testExportProducesValidXliff12(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Bonjour']],
            $this->tempDir
        );

        $xml = simplexml_load_file($this->tempDir . '/translations-fr.xlf');
        $this->assertNotFalse($xml);
        $this->assertSame('1.2', (string) $xml['version']);
    }

    public function testExportContainsSourceAndTarget(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Bonjour']],
            $this->tempDir
        );

        $xml = simplexml_load_file($this->tempDir . '/translations-fr.xlf');
        $fileNode = $xml->file;

        $this->assertSame('en', (string) $fileNode->attributes()['source-language']);
        $this->assertSame('fr', (string) $fileNode->attributes()['target-language']);

        $unit = $fileNode->body->children()[0];
        $this->assertSame('Hello', (string) $unit->source);
        $this->assertSame('Bonjour', (string) $unit->target);
    }

    public function testExportHandlesSpecialXmlCharacters(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            [
                'en' => ['Terms & Conditions' => 'Terms & Conditions'],
                'fr' => ['Terms & Conditions' => 'Termes & Conditions'],
            ],
            $this->tempDir
        );

        $xml = simplexml_load_file($this->tempDir . '/translations-fr.xlf');
        $unit = $xml->file->body->children()[0];

        $this->assertSame('Terms & Conditions', (string) $unit->source);
        $this->assertSame('Termes & Conditions', (string) $unit->target);
    }

    public function testImportReadsTargetLanguageFromAttribute(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello'], 'fr' => ['Hello' => 'Bonjour']],
            $this->tempDir
        );

        $result = $formatter->import($this->tempDir);
        $this->assertArrayHasKey('fr', $result);
        $this->assertArrayNotHasKey('en', $result);
    }

    public function testImportReturnsTranslations(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            ['en' => ['Hello' => 'Hello', 'Goodbye' => 'Goodbye'], 'fr' => ['Hello' => 'Bonjour', 'Goodbye' => 'Au revoir']],
            $this->tempDir
        );

        $result = $formatter->import($this->tempDir);
        $this->assertSame('Bonjour', $result['fr']['Hello']);
        $this->assertSame('Au revoir', $result['fr']['Goodbye']);
    }

    public function testRoundTrip(): void
    {
        $formatter = new XliffFormatter();

        $original = [
            'en' => ['Hello' => 'Hello', 'Goodbye' => 'Goodbye'],
            'fr' => ['Hello' => 'Bonjour', 'Goodbye' => 'Au revoir'],
            'de' => ['Hello' => 'Hallo', 'Goodbye' => 'Auf Wiedersehen'],
        ];

        $formatter->export(['en', 'fr', 'de'], $original, $this->tempDir);
        $imported = $formatter->import($this->tempDir);

        $this->assertSame('Bonjour', $imported['fr']['Hello']);
        $this->assertSame('Au revoir', $imported['fr']['Goodbye']);
        $this->assertSame('Hallo', $imported['de']['Hello']);
        $this->assertSame('Auf Wiedersehen', $imported['de']['Goodbye']);
    }

    public function testExportIncludesKeysFromAllLanguages(): void
    {
        $formatter = new XliffFormatter();
        $formatter->export(
            ['en', 'fr'],
            [
                'en' => ['Hello' => 'Hello', 'World' => 'World'],
                'fr' => ['Hello' => 'Bonjour'],
            ],
            $this->tempDir
        );

        $result = $formatter->import($this->tempDir);
        // "World" exists in en but not fr, should still be in the XLIFF
        $this->assertArrayHasKey('World', $result['fr']);
    }
}
