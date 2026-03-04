<?php

namespace webO3\Translator\Tests\Translators;

use PHPUnit\Framework\TestCase;
use webO3\Translator\Translators\CaseMapper;

class CaseMapperTest extends TestCase
{
    /** @var CaseMapper */
    private $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new CaseMapper();
    }

    public function testDeduplicateGroupsByCaseInsensitiveKey(): void
    {
        $input = [
            'Hello' => 'Hello',
            'hello' => 'hello',
            'HELLO' => 'HELLO',
        ];

        $result = $this->mapper->deduplicate($input);

        $this->assertCount(1, $result['unique']);
        $this->assertArrayHasKey('hello', $result['unique']);
        $this->assertCount(1, $result['groups']);
        $this->assertSame(['Hello', 'hello', 'HELLO'], $result['groups']['hello']);
    }

    public function testDeduplicateKeepsDifferentKeysSepa(): void
    {
        $input = [
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ];

        $result = $this->mapper->deduplicate($input);

        $this->assertCount(2, $result['unique']);
        $this->assertCount(2, $result['groups']);
    }

    public function testApplyCaseUppercase(): void
    {
        $this->assertSame('BONJOUR', $this->mapper->applyCase('bonjour', 'HELLO'));
    }

    public function testApplyCaseLowercase(): void
    {
        $this->assertSame('bonjour', $this->mapper->applyCase('Bonjour', 'hello'));
    }

    public function testApplyCaseTitleCase(): void
    {
        $this->assertSame('Bonjour Le Monde', $this->mapper->applyCase('bonjour le monde', 'Hello World'));
    }

    public function testApplyCaseMixedReturnsAsIs(): void
    {
        // "hELLO" is mixed case — return translation unchanged
        $this->assertSame('bonjour', $this->mapper->applyCase('bonjour', 'hELLO'));
    }

    public function testApplyCaseWithNumbers(): void
    {
        // "123" has no letter casing — return as-is
        $this->assertSame('bonjour', $this->mapper->applyCase('bonjour', '123'));
    }
}
