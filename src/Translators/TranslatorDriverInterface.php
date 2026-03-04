<?php

namespace webO3\Translator\Translators;

interface TranslatorDriverInterface
{
    /**
     * Translate a single string.
     *
     * @param string $text
     * @param string $from Source language code
     * @param string $to   Target language code
     * @return string
     */
    public function translate($text, $from, $to);

    /**
     * Translate multiple strings in a single batch call.
     *
     * @param array  $texts Indexed array of strings
     * @param string $from  Source language code
     * @param string $to    Target language code
     * @return array Indexed array of translated strings (same order)
     */
    public function translateBatch(array $texts, $from, $to);
}
