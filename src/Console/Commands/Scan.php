<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use webO3\Translator\TranslationScanner;

/**
 * Scan source files for translation keys and update language JSON files.
 *
 * Scans *.php, *.js, *.ts and *.vue files in app/ and resources/ for
 * calls to __(), Lang::get(), @lang(), $t() and .t(). Extracted keys are
 * added to resources/lang/{language}.json for each configured language.
 *
 * Detected patterns per file type:
 *   PHP:          __(), Lang::get(), @lang()
 *   JS/TS/Vue:    __(), $t(), .t()
 *
 * Quote handling:
 *   - Single and double quotes with escaped quote support
 *   - Backtick template literals (JS/TS/Vue only, ignored in PHP)
 *
 * @see \webO3\Translator\TranslationScanner
 */
class Scan extends Command
{
    protected $signature = 'translations:scan';
    protected $description = 'Scan source files for translation keys and update language JSON files';

    public function handle()
    {
        $scanner = new TranslationScanner();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs(true)
            ->name('*.js')
            ->name('*.ts')
            ->name('*.php')
            ->name('*.vue');

        $this->info("Extracting texts from files");
        $totalFiles = 0;
        $keys = [];

        // Scan all source files and collect unique translation keys
        foreach ($finder->in([app_path(), resource_path()]) as $file) {
            $totalFiles++;
            $contents = file_get_contents($file->getRealPath());
            $extension = $file->getExtension();

            $fileKeys = $scanner->extractKeys($contents, $extension);
            foreach ($fileKeys as $key) {
                if (!in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        $totalKeys = count($keys);

        $this->warn("Total number of files scanned: {$totalFiles}");
        $this->warn("Number of extracted keys: {$totalKeys}");

        $rows = [];

        // Create or update the JSON file for each configured language
        $languages = config('webo3-translator.languages');
        foreach ($languages as $language) {
            $languageFile = resource_path('lang/'.$language.'.json');

            $json = '';
            $data = [];
            $created = 0;
            $added = 0;

            if (!file_exists($languageFile)) {
                touch($languageFile);
                $created = 1;
            } else {
                $json = file_get_contents($languageFile);
                $data = json_decode($json, true);
            }

            // Add new keys (default value = key itself), preserve existing translations
            foreach ($keys as $key) {
                if (!isset($data[$key]) || $data[$key] == '') {
                    $added++;
                    $data[$key] = $key;
                }
            }

            ksort($data);
            $rows[] = [$language, $created, $added];

            $json = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($languageFile, $json);
        }

        $this->table(['Language', 'Created', 'Added'], $rows);

        $this->info("Files scan extraction completed!");
        $this->line("");
    }
}
