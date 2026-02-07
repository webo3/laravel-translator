<?php

namespace webO3\Translator\Tests\Scanner;

use PHPUnit\Framework\Attributes\DataProvider;
use webO3\Translator\Tests\FixtureTestCase;

class VueScanTest extends FixtureTestCase
{
    public static function vueFixtureProvider(): array
    {
        return self::fixtureFilesInDirectory('vue');
    }

    #[DataProvider('vueFixtureProvider')]
    public function testVueFixture(string $fixturePath): void
    {
        $this->assertFixtureKeys($fixturePath);
    }

    public function testMultiLineBacktickInVue(): void
    {
        $code = "\$t(`Multi\nline\nvue key`)";
        $keys = $this->scanner->extractKeys($code, 'vue');
        $this->assertSame(["Multi\nline\nvue key"], $keys);
    }
}
