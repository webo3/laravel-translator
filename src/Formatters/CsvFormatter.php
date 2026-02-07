<?php

namespace webO3\Translator\Formatters;

class CsvFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export(array $languages, array $translations, $exportPath)
    {
        $headers = array_merge(['key'], $languages);

        // Collect all unique keys across all languages
        $allKeys = [];
        foreach ($translations as $langData) {
            foreach (array_keys($langData) as $key) {
                if (!in_array($key, $allKeys, true)) {
                    $allKeys[] = $key;
                }
            }
        }

        // Build rows
        $rows = [];
        foreach ($allKeys as $key) {
            $row = [$key];
            foreach ($languages as $language) {
                $value = isset($translations[$language][$key]) ? $translations[$language][$key] : '';
                // Leave cell empty if the value is still the key itself (untranslated)
                $row[] = ($value == $key) ? '' : $value;
            }
            $rows[] = $row;
        }

        // Write CSV with UTF-8 BOM for Excel compatibility
        $filePath = rtrim($exportPath, '/') . '/translations.csv';
        $fp = fopen($filePath, 'w');

        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return [$filePath];
    }

    /**
     * {@inheritdoc}
     */
    public function import($exportPath)
    {
        $filePath = rtrim($exportPath, '/') . '/translations.csv';
        $results = [];
        $headers = [];

        if (($handle = fopen($filePath, 'r')) === false) {
            return $results;
        }

        // Skip UTF-8 BOM if present
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $lineNum = 0;
        while (($line = fgetcsv($handle)) !== false) {
            $lineNum++;

            if (empty($headers)) {
                // First row: map column names to indices (key, en, fr, ...)
                foreach ($line as $idx => $value) {
                    $value = trim($value);
                    $headers[$value] = $idx;
                    if ($value != 'key') {
                        $results[$value] = [];
                    }
                }
            } else {
                // Data rows: group translations by language
                $keyIdx = $headers['key'];
                $keyData = $line[$keyIdx];

                foreach ($headers as $colName => $colIdx) {
                    if ($colIdx != $keyIdx) {
                        $results[$colName][$keyData] = isset($line[$colIdx]) ? $line[$colIdx] : $keyData;
                    }
                }
            }
        }

        fclose($handle);

        return $results;
    }
}
