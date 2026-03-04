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
    protected $signature = 'translations:scan {--clean : Remove translation keys no longer found in source files}';
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
        $scanPaths = config('webo3-translator.scan_paths', [app_path(), resource_path()]);
        foreach ($finder->in($scanPaths) as $file) {
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
        $langPath = config('webo3-translator.lang_path', resource_path('lang'));
        $languages = config('webo3-translator.languages');
        foreach ($languages as $language) {
            $languageFile = $langPath . '/' . $language . '.json';

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

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($languageFile, $json);
        }

        $this->table(['Language', 'Created', 'Added'], $rows);

        $this->info("Files scan extraction completed!");
        $this->line("");

        // Clean unused keys if --clean option is set
        if ($this->option('clean')) {
            $this->line("");
            $this->info("Scanning for unused translation keys...");

            $unusedKeysPerLang = [];
            $allUnusedKeys = [];

            foreach ($languages as $language) {
                $languageFile = $langPath . '/' . $language . '.json';
                if (!file_exists($languageFile)) {
                    continue;
                }

                $data = json_decode(file_get_contents($languageFile), true) ?: [];
                $unusedKeys = array_diff(array_keys($data), $keys);
                if (!empty($unusedKeys)) {
                    $unusedKeysPerLang[$language] = $unusedKeys;
                    foreach ($unusedKeys as $key) {
                        $allUnusedKeys[$key] = true;
                    }
                }
            }

            if (empty($allUnusedKeys)) {
                $this->info("No unused translation keys found.");
                return;
            }

            $totalUnused = count($allUnusedKeys);
            $this->warn("Found {$totalUnused} unused translation key(s):");
            $this->line("");

            $tableRows = [];
            foreach (array_keys($allUnusedKeys) as $key) {
                $presentIn = [];
                foreach ($unusedKeysPerLang as $language => $langKeys) {
                    if (in_array($key, $langKeys, true)) {
                        $presentIn[] = $language;
                    }
                }
                $tableRows[] = [$key, implode(', ', $presentIn)];
            }
            $this->table(['Key', 'Languages'], $tableRows);

            if (!$this->confirm("Do you want to remove these {$totalUnused} unused key(s)?")) {
                $this->info("Cleanup cancelled.");
                return;
            }

            $cleanRows = [];
            foreach ($languages as $language) {
                if (!isset($unusedKeysPerLang[$language])) {
                    continue;
                }

                $languageFile = $langPath . '/' . $language . '.json';
                $data = json_decode(file_get_contents($languageFile), true) ?: [];
                $removed = 0;

                foreach ($unusedKeysPerLang[$language] as $key) {
                    unset($data[$key]);
                    $removed++;
                }

                ksort($data);
                file_put_contents($languageFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $cleanRows[] = [$language, $removed];
            }

            $this->table(['Language', 'Removed'], $cleanRows);
            $this->info("Unused translation keys removed successfully!");
        }
    }
}
