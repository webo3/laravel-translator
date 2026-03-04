<?php

namespace webO3\Translator\Translators;

class CaseMapper
{
    /**
     * Group translation keys by their lowercase form to deduplicate API calls.
     *
     * @param array $keysToTranslate [key => sourceValue]
     * @return array ['unique' => [lowercaseKey => sourceValue], 'groups' => [lowercaseKey => [originalKey, ...]]]
     */
    public function deduplicate(array $keysToTranslate)
    {
        $unique = [];
        $groups = [];

        foreach ($keysToTranslate as $key => $sourceValue) {
            $lower = mb_strtolower($key, 'UTF-8');

            if (!isset($groups[$lower])) {
                $groups[$lower] = [];
                $unique[$lower] = mb_strtolower($sourceValue, 'UTF-8');
            }

            $groups[$lower][] = $key;
        }

        return ['unique' => $unique, 'groups' => $groups];
    }

    /**
     * Apply the casing pattern of the original key to a translated string.
     *
     * @param string $translated
     * @param string $originalKey
     * @return string
     */
    public function applyCase($translated, $originalKey)
    {
        if ($this->isAllUpper($originalKey)) {
            return mb_strtoupper($translated, 'UTF-8');
        }

        if ($this->isAllLower($originalKey)) {
            return mb_strtolower($translated, 'UTF-8');
        }

        if ($this->isTitleCase($originalKey)) {
            return mb_convert_case($translated, MB_CASE_TITLE, 'UTF-8');
        }

        // Mixed or sentence case — return as-is from API
        return $translated;
    }

    private function isAllUpper($string)
    {
        return $string === mb_strtoupper($string, 'UTF-8')
            && $string !== mb_strtolower($string, 'UTF-8');
    }

    private function isAllLower($string)
    {
        return $string === mb_strtolower($string, 'UTF-8')
            && $string !== mb_strtoupper($string, 'UTF-8');
    }

    private function isTitleCase($string)
    {
        return $string === mb_convert_case($string, MB_CASE_TITLE, 'UTF-8')
            && $string !== mb_strtolower($string, 'UTF-8')
            && $string !== mb_strtoupper($string, 'UTF-8');
    }
}
