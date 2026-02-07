<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;

/**
 * Export translation JSON files to a single CSV for external translators.
 *
 * Reads resources/lang/{language}.json for each configured language and
 * produces resources/lang/translations.csv with columns: key | en | fr | ...
 *
 * Untranslated values (where value == key) are exported as empty cells.
 * The CSV includes a UTF-8 BOM so Excel opens it correctly.
 */
class Export extends Command
{
    protected $signature = 'translations:export';
    protected $description = 'Export language JSON files to resources/lang/translations.csv';

    public function handle()
    {
        ini_set('auto_detect_line_endings', true);

        $keys = [];
        $rows = [];
        $headers = ['key'];

        $x = 0;
        $y = 0;

        // Aggregate all keys and translations from each language JSON file
        $languages = config('webo3-translator.languages');
        foreach ($languages as $language) {
            $languageFile = resource_path('lang/'.$language.'.json');

            $y++;
            $json = '';
            $data = [];

            if (file_exists($languageFile)) {
                $json = file_get_contents($languageFile);
                $data = json_decode($json, true);

                foreach ($data as $key => $value) {
                    if (!in_array($key, $keys)) {
                        $keys[] = $key;
                    }

                    $x = array_search($key, $keys) + 1;
                    $rows[$x][0] = $key;
                    // Leave cell empty if the value is still the key itself (untranslated)
                    $rows[$x][$y] = ($value == $key ? "" : $value);
                }

                $headers[$y] = $language;
            }
        }

        // Write CSV with UTF-8 BOM for Excel compatibility
        $transFile = resource_path('lang/translations.csv');
        $fp = fopen($transFile, 'w');

        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        $this->info("Translations exportation completed!");
        $this->info("File exported to: {$transFile}");
        $this->line("");
    }
}
