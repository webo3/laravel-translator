<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use webO3\Translator\Formatters\CsvFormatter;
use webO3\Translator\Formatters\XliffFormatter;

/**
 * Import translations from a file back into language JSON files.
 *
 * Reads the translation file (CSV or XLIFF) produced by translations:export
 * and merges the translated values into {lang_path}/{language}.json.
 *
 * - New keys are added
 * - Changed translations are updated
 * - Empty values default to the key itself
 * - Keys are sorted alphabetically after merge
 */
class Import extends Command
{
    protected $signature = 'translations:import';
    protected $description = 'Import translations from a translation file (CSV or XLIFF) into JSON files';

    public function handle()
    {
        $langPath = config('webo3-translator.lang_path', resource_path('lang'));
        $exportPath = config('webo3-translator.export_path') ?: $langPath;

        $this->info("Importing translations...");

        $formatter = $this->resolveFormatter();
        $importedData = $formatter->import($exportPath);

        $statuses = [];

        foreach ($importedData as $language => $texts) {
            $languageFile = $langPath . '/' . $language . '.json';
            $updated = 0;
            $added = 0;

            $data = [];
            if (file_exists($languageFile)) {
                $data = json_decode(file_get_contents($languageFile), true) ?: [];
            }

            foreach ($texts as $key => $value) {
                // Add new keys
                if (!isset($data[$key]) || $data[$key] == '') {
                    $added++;
                    $data[$key] = ($value == '' ? $key : $value);
                }
                // Update changed translations
                elseif ($data[$key] != $value) {
                    $updated++;
                    $data[$key] = ($value == '' ? $key : $value);
                }
            }

            ksort($data);

            $json = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($languageFile, $json);

            $statuses[] = [$language, $updated, $added];
        }

        $this->table(['Language', 'Updated', 'Added'], $statuses);

        $this->info("Translations importation completed!");
        $this->line("");
    }

    private function resolveFormatter()
    {
        $format = config('webo3-translator.format', 'csv');

        if ($format === 'xliff') {
            return new XliffFormatter();
        }

        return new CsvFormatter();
    }
}
