<?php

namespace webO3\Translator\Tests\Scanner;

use PHPUnit\Framework\Attributes\DataProvider;
use webO3\Translator\Tests\FixtureTestCase;

class TsScanTest extends FixtureTestCase
{
    public static function tsFixtureProvider(): array
    {
        return self::fixtureFilesInDirectory('ts');
    }

    #[DataProvider('tsFixtureProvider')]
    public function testTsFixture(string $fixturePath): void
    {
        $this->assertFixtureKeys($fixturePath);
    }
}
