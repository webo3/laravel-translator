<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class Scan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan files for texts to be extracted and convert them to JSON';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs(true)
            ->name('*.js')
            ->name('*.php')
            ->name('*.vue');

        $this->info("Extracting texts from files");
        $totalFiles = 0;
        $totalKeys = 0;

        // Get all texts keys
        $keys = [];

        foreach ($finder->in([app_path(), resource_path()]) as $file) {
            $totalFiles++;
            $filePath = $file->getRealPath();

            // get clean file content
            $contents = file_get_contents($filePath);

            // delete line comments
            $contents = preg_replace("#(//.*?)$#m", '', $contents);

            // delete multiline comments
            $contents = preg_replace('#/\*(.*?)\*/#is', '', $contents);

            // Extract function __()
            if (preg_match_all('#(__|@lang|\.t|\$t)\((\'|"|`)(.*?)(\2),?.*?\)\;?#is', $contents, $matches)) {
                $count = sizeof($matches[3]);

                for ($i=0; $i<$count; $i++) {
                    $key = trim($matches[3][$i]);
                    if (!in_array($key, $keys)) {
                        $keys[] = $key;
                        $totalKeys++;
                    }
                }
            }
        }

        $this->warn("Total number of files scanned: ${totalFiles}");
        $this->warn("Number of extracted keys: ${totalKeys}");

        $rows = [];

        // Update and prepare files
        $languages = config('webo3-translator.languages');
        foreach ($languages as $language) {
            $languageFile = resource_path('lang/'.$language.'.json');

            // Reset vars
            $json = '';
            $data = [];
            $created = 0;
            $added = 0;

            // Create the file
            if (!file_exists($languageFile)) {
                touch($languageFile);
                $created = 1;
            } else {
                // Load the file
                $json = file_get_contents($languageFile);
                $data = json_decode($json, true);
            }

            // New keys
            foreach ($keys as $key) {
                if (!isset($data[$key]) || $data[$key] == '') {
                    $added++;
                    $data[$key] = $key;
                }
            }

            ksort($data);

            // Prepare language
            $rows[] = [$language, $created, $added];

            // Write the file
            $json = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($languageFile, $json);
        }

        $this->table([
            'Language', 'Created', 'Added'
        ], $rows);

        $this->info("Files scan extraction completed!");
        $this->line("");
    }
}
