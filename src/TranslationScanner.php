<?php

namespace webO3\Translator;

class TranslationScanner
{
    /**
     * Extract translation keys from file content.
     *
     * @param string $contents Raw file contents
     * @param string $extension File extension (e.g., 'php', 'js', 'vue')
     * @return array Array of unique translation key strings
     */
    public function extractKeys(string $contents, string $extension): array
    {
        $contents = $this->removeComments($contents);
        $pattern = $this->buildPattern($extension);

        $keys = [];
        if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = null;
                $quoteChar = null;

                // Determine which quote group matched (2=single, 3=double, 4=backtick)
                if (isset($match[2]) && $match[2] !== '') {
                    $key = $match[2];
                    $quoteChar = "'";
                } elseif (isset($match[3]) && $match[3] !== '') {
                    $key = $match[3];
                    $quoteChar = '"';
                } elseif (isset($match[4]) && $match[4] !== '') {
                    $key = $match[4];
                    $quoteChar = '`';
                }

                if ($key !== null) {
                    $key = $this->unescapeKey($key, $quoteChar);
                    $key = trim($key);
                    if ($key !== '' && !in_array($key, $keys, true)) {
                        $keys[] = $key;
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Remove comments from source code without stripping // inside strings.
     *
     * Uses a regex that matches string literals before comments so that
     * strings containing // (e.g., URLs) are preserved intact.
     */
    public function removeComments(string $contents): string
    {
        $result = preg_replace_callback(
            '#'
            // Group 1: single-quoted string (with escaped quotes)
            . "(\'(?:[^\'\\\\]|\\\\.)*\')"
            . '|'
            // Group 2: double-quoted string (with escaped quotes)
            . '("(?:[^"\\\\]|\\\\.)*")'
            . '|'
            // Group 3: backtick string (with escaped chars, can span lines)
            . '(`(?:[^`\\\\]|\\\\[\\s\\S])*`)'
            . '|'
            // Group 4: multi-line comment
            . '(/\\*[\\s\\S]*?\\*/)'
            . '|'
            // Group 5: single-line comment (stops at \n or \r)
            . '(//[^\\n\\r]*)'
            . '#',
            function ($match) {
                // Keep string literals (groups 1-3), remove comments (groups 4-5)
                if (!empty($match[1]) || !empty($match[2]) || !empty($match[3])) {
                    return $match[0];
                }
                return '';
            },
            $contents
        );

        return $result ?? $contents;
    }

    /**
     * Build the regex pattern for extracting translation keys.
     *
     * The pattern varies by file extension:
     * - PHP: Lang::get, __, @lang (single/double quotes only)
     * - JS/TS/Vue: __, $t, .t (single/double quotes + backticks)
     */
    public function buildPattern(string $extension): string
    {
        $functions = $this->getFunctionPatterns($extension);

        // Each quote type has its own capture group with proper escape handling
        $quotePatterns = [
            // Group 2: single-quoted key (no newlines \n or \r allowed)
            "'((?:[^'\\\\\\r\\n]|\\\\.)*)'",
            // Group 3: double-quoted key (no newlines \n or \r allowed)
            '"((?:[^"\\\\\\r\\n]|\\\\.)*)"',
        ];

        // Backtick only for JS-family files (in PHP, backticks execute shell commands)
        if (in_array($extension, ['js', 'ts', 'vue', 'jsx', 'tsx'])) {
            // Group 4: backtick key (can span lines via [\s\S])
            $quotePatterns[] = '`((?:[^`\\\\]|\\\\[\\s\\S])*)`';
        }

        $quotePart = '(?:' . implode('|', $quotePatterns) . ')';

        // Full pattern: function_name( quote_pattern [, optional_args] )
        // Using #i flag (case-insensitive) but NOT #s (no dot-matches-newline)
        return '#(?:' . $functions . ')\\(\\s*' . $quotePart . '\\s*(?:,.*?)?\\)#i';
    }

    /**
     * Get the function name regex alternatives for the given file extension.
     */
    public function getFunctionPatterns(string $extension): string
    {
        // __() is universal across all file types
        $patterns = ['__'];

        if ($extension === 'php') {
            $patterns[] = 'Lang::get';
            $patterns[] = '@lang';
        }

        if (in_array($extension, ['js', 'ts', 'vue', 'jsx', 'tsx'])) {
            $patterns[] = '\\$t';          // Vue: $t('key') or this.$t('key')
            $patterns[] = '(?<=\\.)t';     // i18n.t('key') - lookbehind requires dot before t
        }

        return '(' . implode('|', $patterns) . ')';
    }

    /**
     * Unescape a matched key by resolving escaped quote characters.
     */
    public function unescapeKey(string $key, string $quoteChar): string
    {
        return str_replace('\\' . $quoteChar, $quoteChar, $key);
    }
}
