<?php

namespace webO3\Translator\Tests\Translators;

use PHPUnit\Framework\TestCase;
use webO3\Translator\Translators\PlaceholderGuard;

class PlaceholderGuardTest extends TestCase
{
    /** @var PlaceholderGuard */
    private $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new PlaceholderGuard();
    }

    public function testMasksLaravelPlaceholder(): void
    {
        $masked = $this->guard->mask('Welcome :name');
        $this->assertStringNotContainsString(':name', $masked);
        $this->assertStringContainsString('<x id="0"/>', $masked);
    }

    public function testMasksBracePlaceholder(): void
    {
        $masked = $this->guard->mask('Hello {user}');
        $this->assertStringNotContainsString('{user}', $masked);
        $this->assertStringContainsString('<x id="0"/>', $masked);
    }

    public function testUnmaskRestoresLaravelPlaceholder(): void
    {
        $masked = $this->guard->mask('Welcome :name to :app');
        $unmasked = $this->guard->unmask($masked);
        $this->assertSame('Welcome :name to :app', $unmasked);
    }

    public function testUnmaskRestoresBracePlaceholder(): void
    {
        $masked = $this->guard->mask('Hello {user}, you have {count} items');
        $unmasked = $this->guard->unmask($masked);
        $this->assertSame('Hello {user}, you have {count} items', $unmasked);
    }

    public function testRoundTripWithMixedPlaceholders(): void
    {
        $original = 'Dear :name, your {item} is ready';
        $masked = $this->guard->mask($original);
        $this->assertSame($original, $this->guard->unmask($masked));
    }

    public function testTextWithoutPlaceholdersPassesThrough(): void
    {
        $text = 'Hello world';
        $masked = $this->guard->mask($text);
        $this->assertSame($text, $masked);
        $this->assertSame($text, $this->guard->unmask($masked));
    }

    public function testUnmaskHandlesMangledTags(): void
    {
        $this->guard->mask('Hello :name');
        // Simulate API mangling the tag with extra spaces
        $mangled = 'Bonjour <x id="0" />';
        $this->assertSame('Bonjour :name', $this->guard->unmask($mangled));
    }

    public function testUnmaskHandlesTagWithoutSelfClosingSlash(): void
    {
        $this->guard->mask('Hello :name');
        $mangled = 'Bonjour <x id="0">';
        $this->assertSame('Bonjour :name', $this->guard->unmask($mangled));
    }

    public function testMultiplePlaceholdersGetUniqueIds(): void
    {
        $masked = $this->guard->mask(':first and :second');
        $this->assertStringContainsString('<x id="0"/>', $masked);
        $this->assertStringContainsString('<x id="1"/>', $masked);
    }
}
