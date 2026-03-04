<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use webO3\Translator\Translators\CaseMapper;
use webO3\Translator\Translators\GoogleFreeTranslateDriver;
use webO3\Translator\Translators\TranslatorDriverInterface;

class Translate extends Command
{
    protected $signature = 'translations:translate
        {--lang= : Comma-separated target language codes (default: all except source)}
        {--driver= : Translation driver to use (overrides config)}
        {--force : Re-translate keys that already have translations}
        {--batch-size=50 : Number of strings to translate per API call}';

    protected $description = 'Auto-translate untranslated keys using a translation API';

    public function handle()
    {
        $langPath = config('webo3-translator.lang_path', resource_path('lang'));
        $languages = config('webo3-translator.languages');
        $sourceLang = $languages[0];
        $force = $this->option('force');
        $batchSize = (int) $this->option('batch-size');

        // Determine target languages
        $targetLangs = array_slice($languages, 1);
        if ($langOption = $this->option('lang')) {
            $requested = explode(',', $langOption);
            $targetLangs = array_intersect($requested, $targetLangs);
        }

        if (empty($targetLangs)) {
            $this->error('No target languages to translate.');
            return 1;
        }

        // Load source translations
        $sourceFile = $langPath . '/' . $sourceLang . '.json';
        if (!file_exists($sourceFile)) {
            $this->error("Source language file not found: {$sourceFile}");
            return 1;
        }
        $sourceData = json_decode(file_get_contents($sourceFile), true) ?: [];

        $driver = $this->resolveDriver();
        $caseMapper = new CaseMapper();
        $rows = [];

        foreach ($targetLangs as $targetLang) {
            $targetFile = $langPath . '/' . $targetLang . '.json';
            $targetData = [];
            if (file_exists($targetFile)) {
                $targetData = json_decode(file_get_contents($targetFile), true) ?: [];
            }

            // Collect keys that need translation
            $toTranslate = [];
            foreach ($sourceData as $key => $sourceValue) {
                $currentValue = $targetData[$key] ?? $key;
                $isUntranslated = ($currentValue === $key);
                if ($force || $isUntranslated) {
                    $toTranslate[$key] = $sourceValue;
                }
            }

            $skippedCount = count($sourceData) - count($toTranslate);

            if (empty($toTranslate)) {
                $this->info("[{$targetLang}] Nothing to translate.");
                $rows[] = [$targetLang, 0, $skippedCount, 0, 0];
                continue;
            }

            // Deduplicate by case
            $dedup = $caseMapper->deduplicate($toTranslate);
            $uniqueKeys = array_keys($dedup['unique']);
            $uniqueValues = array_values($dedup['unique']);
            $dedupSaved = count($toTranslate) - count($uniqueKeys);

            $this->info("[{$targetLang}] Translating " . count($uniqueKeys) . " unique keys"
                . ($dedupSaved > 0 ? " ({$dedupSaved} case variants deduplicated)" : '') . '...');

            // Batch translate unique values
            $chunks = array_chunk(range(0, count($uniqueValues) - 1), $batchSize);
            $bar = $this->output->createProgressBar(count($uniqueValues));
            $bar->start();

            $translatedUnique = [];
            $errors = 0;

            foreach ($chunks as $chunkIndices) {
                $batchTexts = [];
                foreach ($chunkIndices as $idx) {
                    $batchTexts[] = $uniqueValues[$idx];
                }

                try {
                    $results = $driver->translateBatch($batchTexts, $sourceLang, $targetLang);
                    foreach ($chunkIndices as $j => $idx) {
                        $translatedUnique[$uniqueKeys[$idx]] = $results[$j];
                    }
                } catch (RuntimeException $e) {
                    $errors += count($chunkIndices);
                    $this->newLine();
                    $this->warn("Batch error: " . $e->getMessage());
                }

                $bar->advance(count($chunkIndices));
            }

            $bar->finish();
            $this->newLine();

            // Map translations back to all case variants
            $translatedCount = 0;
            foreach ($dedup['groups'] as $lowerKey => $originalKeys) {
                if (!isset($translatedUnique[$lowerKey])) {
                    continue;
                }
                $baseTranslation = $translatedUnique[$lowerKey];

                foreach ($originalKeys as $originalKey) {
                    $targetData[$originalKey] = $caseMapper->applyCase($baseTranslation, $originalKey);
                    $translatedCount++;
                }
            }

            // Sort and write
            ksort($targetData);
            file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $rows[] = [$targetLang, $translatedCount, $skippedCount, $dedupSaved, $errors];
        }

        $this->newLine();
        $this->table(
            ['Language', 'Translated', 'Skipped', 'Deduplicated', 'Errors'],
            $rows
        );

        $this->info('Auto-translation completed!');
        $this->line('');

        return 0;
    }

    /**
     * @return TranslatorDriverInterface
     */
    private function resolveDriver()
    {
        $driverName = $this->option('driver')
            ?: config('webo3-translator.translation_driver', 'google_free');

        // Allow app-bound custom drivers (useful for testing)
        $binding = 'translation.driver.' . $driverName;
        if (app()->bound($binding)) {
            return app($binding);
        }

        switch ($driverName) {
            case 'google_free':
                return new GoogleFreeTranslateDriver();
            default:
                throw new RuntimeException("Unknown translation driver: {$driverName}");
        }
    }
}
