<?php

namespace webO3\Translator\Tests\Scanner;

use PHPUnit\Framework\Attributes\DataProvider;
use webO3\Translator\Tests\FixtureTestCase;

class PhpScanTest extends FixtureTestCase
{
    public static function phpFixtureProvider(): array
    {
        return self::fixtureFilesInDirectory('php');
    }

    #[DataProvider('phpFixtureProvider')]
    public function testPhpFixture(string $fixturePath): void
    {
        $this->assertFixtureKeys($fixturePath);
    }

    public function testMultipleTranslationsOnSameLine(): void
    {
        $code = '<?php echo __(\'First\') . " " . __("Second");';
        $keys = $this->scanner->extractKeys($code, 'php');
        $this->assertContains('First', $keys);
        $this->assertContains('Second', $keys);
        $this->assertCount(2, $keys);
    }

    public function testSingleQuoteDoesNotSpanLines(): void
    {
        $code = "<?php __('First line\nSecond line');";
        $this->assertSame([], $this->scanner->extractKeys($code, 'php'));
    }

    public function testDoubleQuoteDoesNotSpanLines(): void
    {
        $code = "<?php __(\"First line\nSecond line\");";
        $this->assertSame([], $this->scanner->extractKeys($code, 'php'));
    }

    public function testSingleQuoteDoesNotSpanWindowsLines(): void
    {
        $code = "<?php __('First line\r\nSecond line');";
        $this->assertSame([], $this->scanner->extractKeys($code, 'php'));
    }

    public function testDoubleQuoteDoesNotSpanWindowsLines(): void
    {
        $code = "<?php __(\"First line\r\nSecond line\");";
        $this->assertSame([], $this->scanner->extractKeys($code, 'php'));
    }

    public function testSingleQuoteDoesNotSpanCarriageReturn(): void
    {
        $code = "<?php __('First line\rSecond line');";
        $this->assertSame([], $this->scanner->extractKeys($code, 'php'));
    }

    public function testCaseInsensitiveLangGet(): void
    {
        $code = "<?php echo lang::get('messages.welcome');";
        $keys = $this->scanner->extractKeys($code, 'php');
        $this->assertSame(['messages.welcome'], $keys);
    }
}
