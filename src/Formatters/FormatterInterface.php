<?php

namespace webO3\Translator\Formatters;

interface FormatterInterface
{
    /**
     * Export translations to file(s).
     *
     * @param array  $languages    Ordered list of language codes (e.g., ['en', 'fr'])
     * @param array  $translations Associative array keyed by language code,
     *                             each containing [key => value] pairs
     * @param string $exportPath   Directory to write the output file(s) to
     * @return array List of file paths that were created
     */
    public function export(array $languages, array $translations, $exportPath);

    /**
     * Import translations from file(s).
     *
     * @param string $exportPath Directory containing the file(s) to import from
     * @return array Associative array keyed by language code,
     *               each containing [key => value] pairs
     */
    public function import($exportPath);
}
