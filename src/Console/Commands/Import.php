<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;

/**
 * Import translations from CSV back into language JSON files.
 *
 * Reads resources/lang/translations.csv (produced by translations:export)
 * and merges the translated values into resources/lang/{language}.json.
 *
 * - New keys are added
 * - Changed translations are updated
 * - Empty CSV cells default to the key itself
 * - Keys are sorted alphabetically after merge
 * - Handles UTF-8 BOM produced by Excel
 */
class Import extends Command
{
    protected $signature = 'translations:import';
    protected $description = 'Import translations from resources/lang/translations.csv into JSON files';

    public function handle()
    {
        $this->info("Importing texts from lang/translations.csv");

        ini_set('auto_detect_line_endings', true);

        $headers = [];
        $datas = [];
        $updated = 0;
        $added = 0;
        $numLine = 0;
        $statuses = [];

        // Read and parse the CSV file
        $transFile = resource_path('lang/translations.csv');
        if (($handle = fopen($transFile, "r")) !== false) {
            // Skip UTF-8 BOM if present
            $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
            if (fgets($handle, 4) !== $bom) {
                rewind($handle);
            }

            while (($line = fgetcsv($handle)) !== false) {
                $numLine++;

                if (empty($headers)) {
                    // First row: map column names to indices (key, en, fr, ...)
                    foreach ($line as $key => $value) {
                        $value = trim($value);
                        $key = trim($key);
                        $headers[$value] = $key;
                        if ($value != 'key') {
                            $datas[$value] = [];
                        }
                    }
                } else {
                    // Data rows: group translations by language
                    $keyIdx = $headers['key'];
                    $keyData = $line[$keyIdx];

                    foreach ($headers as $key => $value) {
                        if ($value != $keyIdx) {
                            $datas[$key][$keyData] = isset($line[$value]) ? $line[$value] : $keyData;
                        }
                    }
                }
            }
            fclose($handle);
        }

        $totalLang = count($datas);
        $this->warn("Total languages in the file: {$totalLang}");
        $this->warn("Total lines to merge: {$numLine}");

        // Merge CSV data into each language JSON file
        foreach ($datas as $language => $texts) {
            $languageFile = resource_path('lang/'.$language.'.json');

            $json = file_get_contents($languageFile);
            $data = json_decode($json, true);

            foreach ($texts as $key => $value) {
                // Add new keys
                if (!isset($data[$key]) || $data[$key] == '') {
                    $added++;
                    $data[$key] = ($value == '' ? $key : $value);
                }
                // Update changed translations
                if ($data[$key] != $value) {
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
}
