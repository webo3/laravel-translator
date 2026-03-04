<?php

namespace webO3\Translator\Tests\Integration;

use webO3\Translator\Translators\TranslatorDriverInterface;

class TranslateCommandTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureLangDirectory();
        $this->bindFakeDriver();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('webo3-translator.translation_driver', 'google_free');
    }

    private function seedFiles(array $en, array $fr = []): void
    {
        $langPath = resource_path('lang');
        file_put_contents($langPath . '/en.json', json_encode($en, JSON_PRETTY_PRINT));
        if (!empty($fr)) {
            file_put_contents($langPath . '/fr.json', json_encode($fr, JSON_PRETTY_PRINT));
        }
    }

    private function bindFakeDriver(): void
    {
        $this->app->singleton('translation.driver.fake', function () {
            return new FakeTranslateDriver();
        });
    }

    public function testTranslatesUntranslatedKeys(): void
    {
        $this->seedFiles([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ]);

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
        ])->assertSuccessful();

        $fr = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        $this->assertSame('[Fr] Hello', $fr['Hello']);
        $this->assertSame('[Fr] Goodbye', $fr['Goodbye']);
    }

    public function testSkipsAlreadyTranslatedKeys(): void
    {
        $this->seedFiles(
            ['Hello' => 'Hello', 'Goodbye' => 'Goodbye'],
            ['Hello' => 'Bonjour', 'Goodbye' => 'Goodbye']
        );

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
        ])->assertSuccessful();

        $fr = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        $this->assertSame('Bonjour', $fr['Hello']); // preserved
        $this->assertSame('[Fr] Goodbye', $fr['Goodbye']); // translated
    }

    public function testForceOverwritesExisting(): void
    {
        $this->seedFiles(
            ['Hello' => 'Hello'],
            ['Hello' => 'Bonjour']
        );

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
            '--force' => true,
        ])->assertSuccessful();

        $fr = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        $this->assertSame('[Fr] Hello', $fr['Hello']);
    }

    public function testLangOptionFiltersTargetLanguages(): void
    {
        $this->app['config']->set('webo3-translator.languages', ['en', 'fr', 'es']);

        $this->seedFiles(['Hello' => 'Hello']);

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
        ])->assertSuccessful();

        // fr.json should exist, es.json should not
        $this->assertFileExists(resource_path('lang/fr.json'));
        $this->assertFileDoesNotExist(resource_path('lang/es.json'));
    }

    public function testCaseDeduplication(): void
    {
        $this->seedFiles([
            'Hello' => 'Hello',
            'hello' => 'hello',
            'HELLO' => 'HELLO',
        ]);

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
        ])->assertSuccessful();

        $fr = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        // All three should be translated with appropriate casing
        $this->assertSame('[fr] hello', $fr['hello']);      // lowercase
        $this->assertSame('[FR] HELLO', $fr['HELLO']);      // uppercase
        // "Hello" is title case
        $this->assertSame('[Fr] Hello', $fr['Hello']);
    }

    public function testErrorWhenNoTargetLanguages(): void
    {
        $this->app['config']->set('webo3-translator.languages', ['en']);
        $this->seedFiles(['Hello' => 'Hello']);

        $this->artisan('translations:translate', [
            '--driver' => 'fake',
        ])->assertFailed();
    }

    public function testPreservesPlaceholders(): void
    {
        $this->seedFiles([
            'Welcome :name' => 'Welcome :name',
        ]);

        $this->artisan('translations:translate', [
            '--lang' => 'fr',
            '--driver' => 'fake',
        ])->assertSuccessful();

        $fr = json_decode(file_get_contents(resource_path('lang/fr.json')), true);
        $this->assertStringContainsString(':name', $fr['Welcome :name']);
    }
}

/**
 * Fake translation driver for testing. Prefixes text with [lang] and lowercases.
 */
class FakeTranslateDriver implements TranslatorDriverInterface
{
    public function translate($text, $from, $to)
    {
        return $this->doTranslate($text, $to);
    }

    public function translateBatch(array $texts, $from, $to)
    {
        return array_map(function ($text) use ($to) {
            return $this->doTranslate($text, $to);
        }, $texts);
    }

    private function doTranslate($text, $to)
    {
        return "[{$to}] {$text}";
    }
}
