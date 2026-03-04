<?php

namespace webO3\Translator\Translators;

class PlaceholderGuard
{
    /** @var array Map of XML tag => original placeholder */
    private $map = [];

    /**
     * Replace :word and {word} placeholders with XML sentinel tags.
     *
     * @param string $text
     * @return string
     */
    public function mask($text)
    {
        $this->map = [];
        $counter = 0;

        return preg_replace_callback('/:[a-zA-Z_]\w*|\{[a-zA-Z_]\w*\}/', function ($match) use (&$counter) {
            $tag = '<x id="' . $counter . '"/>';
            $this->map[$tag] = $match[0];
            $counter++;
            return $tag;
        }, $text);
    }

    /**
     * Restore original placeholders from XML sentinel tags.
     *
     * @param string $translated
     * @return string
     */
    public function unmask($translated)
    {
        // Exact match first
        foreach ($this->map as $tag => $original) {
            $translated = str_replace($tag, $original, $translated);
        }

        // Handle mangled tags (extra spaces, missing slash, etc.)
        $map = $this->map;
        $translated = preg_replace_callback(
            '/<x\s+id\s*=\s*["\']?(\d+)["\']?\s*\/?\s*>/',
            function ($match) use ($map) {
                $tag = '<x id="' . (int) $match[1] . '"/>';
                return $map[$tag] ?? $match[0];
            },
            $translated
        );

        return $translated;
    }
}
