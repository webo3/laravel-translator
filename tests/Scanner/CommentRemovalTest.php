<?php

namespace webO3\Translator\Tests\Scanner;

use webO3\Translator\Tests\FixtureTestCase;

class CommentRemovalTest extends FixtureTestCase
{
    public function testPreservesUrlInSingleQuotedString(): void
    {
        $input = "\$url = 'http://example.com'; // a comment";
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('http://example.com', $result);
        $this->assertStringNotContainsString('// a comment', $result);
    }

    public function testPreservesUrlInDoubleQuotedString(): void
    {
        $input = '$url = "http://example.com"; // comment';
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('http://example.com', $result);
        $this->assertStringNotContainsString('// comment', $result);
    }

    public function testStripsBlockComment(): void
    {
        $input = "before /* middle */ after";
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('before', $result);
        $this->assertStringContainsString('after', $result);
        $this->assertStringNotContainsString('middle', $result);
    }

    public function testStripsMultiLineBlockComment(): void
    {
        $input = "before\n/*\nmiddle\n*/\nafter";
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('before', $result);
        $this->assertStringContainsString('after', $result);
        $this->assertStringNotContainsString('middle', $result);
    }

    public function testStripsLineComment(): void
    {
        $input = "code(); // this is a comment\nmore code();";
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('code()', $result);
        $this->assertStringContainsString('more code()', $result);
        $this->assertStringNotContainsString('this is a comment', $result);
    }

    public function testHandlesWindowsLineEndings(): void
    {
        $input = "code(); // comment\r\nmore();";
        $result = $this->scanner->removeComments($input);
        $this->assertStringContainsString('code()', $result);
        $this->assertStringContainsString('more()', $result);
        $this->assertStringNotContainsString('// comment', $result);
    }

    public function testEmptyInputReturnsEmpty(): void
    {
        $this->assertSame('', $this->scanner->removeComments(''));
    }
}
