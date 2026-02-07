<?php

namespace webO3\Translator\Tests\Scanner;

use PHPUnit\Framework\Attributes\DataProvider;
use webO3\Translator\Tests\FixtureTestCase;

class JsScanTest extends FixtureTestCase
{
    public static function jsFixtureProvider(): array
    {
        return self::fixtureFilesInDirectory('js');
    }

    #[DataProvider('jsFixtureProvider')]
    public function testJsFixture(string $fixturePath): void
    {
        $this->assertFixtureKeys($fixturePath);
    }

    public function testMultiLineBacktickWithSingleQuotesInside(): void
    {
        $code = "__(`It's a\nmulti-line test`)";
        $keys = $this->scanner->extractKeys($code, 'js');
        $this->assertSame(["It's a\nmulti-line test"], $keys);
    }

    public function testMultiLineBacktickWithDoubleQuotesInside(): void
    {
        $code = "__(`He said \"hello\"\non multiple lines`)";
        $keys = $this->scanner->extractKeys($code, 'js');
        $this->assertSame(["He said \"hello\"\non multiple lines"], $keys);
    }

    public function testMultiLineBacktickWithMixedQuotes(): void
    {
        $code = "__(`It's a \"test\"\nwith 'mixed' quotes\non three lines`)";
        $keys = $this->scanner->extractKeys($code, 'js');
        $this->assertSame(["It's a \"test\"\nwith 'mixed' quotes\non three lines"], $keys);
    }

    public function testMultiLineBacktickWithCarriageReturn(): void
    {
        $code = "__(`Line one\rLine two`)";
        $keys = $this->scanner->extractKeys($code, 'js');
        $this->assertSame(["Line one\rLine two"], $keys);
    }
}
