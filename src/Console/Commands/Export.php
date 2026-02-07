<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use webO3\Translator\Formatters\CsvFormatter;
use webO3\Translator\Formatters\XliffFormatter;

/**
 * Export translation JSON files for external translators.
 *
 * Reads {lang_path}/{language}.json for each configured language and
 * produces translation files in the configured format (CSV or XLIFF).
 */
class Export extends Command
{
    protected $signature = 'translations:export';
    protected $description = 'Export language JSON files to a translation file (CSV or XLIFF)';

    public function handle()
    {
        $langPath = config('webo3-translator.lang_path', resource_path('lang'));
        $exportPath = config('webo3-translator.export_path') ?: $langPath;
        $languages = config('webo3-translator.languages');

        // Load translations from JSON files
        $translations = [];
        foreach ($languages as $language) {
            $file = $langPath . '/' . $language . '.json';
            $translations[$language] = [];
            if (file_exists($file)) {
                $translations[$language] = json_decode(file_get_contents($file), true) ?: [];
            }
        }

        $formatter = $this->resolveFormatter();
        $createdFiles = $formatter->export($languages, $translations, $exportPath);

        $this->info("Translations exportation completed!");
        foreach ($createdFiles as $file) {
            $this->info("File exported to: {$file}");
        }
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
