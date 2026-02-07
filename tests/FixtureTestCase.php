<?php

namespace webO3\Translator\Tests;

use PHPUnit\Framework\TestCase;
use webO3\Translator\TranslationScanner;

abstract class FixtureTestCase extends TestCase
{
    protected TranslationScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new TranslationScanner();
    }

    /**
     * Get the path to the fixtures directory.
     */
    protected function fixturesPath(string $relativePath = ''): string
    {
        return __DIR__ . '/fixtures' . ($relativePath ? '/' . $relativePath : '');
    }

    /**
     * Load a fixture file and return its content.
     */
    protected function loadFixture(string $relativePath): string
    {
        $path = $this->fixturesPath($relativePath);
        $this->assertFileExists($path, "Fixture file not found: {$relativePath}");

        return file_get_contents($path);
    }

    /**
     * Parse @expect comments from fixture file content.
     *
     * Supported formats:
     *   // @expect: key text here
     *   <!-- @expect: key text here -->
     *   // @expect-none              (file should yield no keys)
     *   <!-- @expect-none -->
     *
     * For multi-line keys, use literal \n or \r\n in the @expect value:
     *   // @expect: Line one\nLine two
     *
     * @return array Expected keys, or empty array if @expect-none
     */
    protected function parseExpectedKeys(string $contents): array
    {
        // Check for @expect-none first
        if (preg_match('#@expect-none#', $contents)) {
            return [];
        }

        $keys = [];
        // Match both // @expect: ... and <!-- @expect: ... -->
        if (preg_match_all('#@expect:\s*(.+?)(?:\s*-->)?$#m', $contents, $matches)) {
            foreach ($matches[1] as $key) {
                $key = trim($key);
                // Convert literal \n and \r to actual newlines
                $key = str_replace(['\\r\\n', '\\r', '\\n'], ["\r\n", "\r", "\n"], $key);
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Get the file extension from a fixture path.
     */
    protected function getExtension(string $relativePath): string
    {
        return pathinfo($relativePath, PATHINFO_EXTENSION);
    }

    /**
     * Assert that a fixture file produces the expected translation keys.
     *
     * Reads the fixture, parses @expect comments, runs the scanner,
     * and compares results.
     */
    protected function assertFixtureKeys(string $relativePath): void
    {
        $contents = $this->loadFixture($relativePath);
        $extension = $this->getExtension($relativePath);
        $expected = $this->parseExpectedKeys($contents);
        $actual = $this->scanner->extractKeys($contents, $extension);

        $this->assertSame(
            $expected,
            $actual,
            "Fixture {$relativePath}: expected keys do not match extracted keys."
        );
    }

    /**
     * Scan all fixtures in a directory and assert each one.
     *
     * Useful for running all fixtures of a given type (e.g., all php/ files).
     *
     * @return array<string, array> Data provider-style array [name => [path]]
     */
    protected static function fixtureFilesInDirectory(string $subdir): array
    {
        $dir = __DIR__ . '/fixtures/' . $subdir;
        $files = glob($dir . '/*');
        $cases = [];

        foreach ($files as $file) {
            $name = basename($file);
            $cases[$name] = [$subdir . '/' . $name];
        }

        return $cases;
    }
}
