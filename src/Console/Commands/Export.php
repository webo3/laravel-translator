<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class Export extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take the JSON texts files and export them to lang/translations.csv';

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
        ini_set('auto_detect_line_endings', true);

        $keys = [];
        $rows = [];
        $headers = [
            'key'
        ];

        $x = 0;
        $y = 0;

        // Get all languages
        $languages = config('webo3-translator.languages');
        foreach ($languages as $language) {
            $languageFile = resource_path('lang/'.$language.'.json');

            $y++;

            // Reset vars
            $json = '';
            $data = [];

            // Load the file
            if (file_exists($languageFile)) {
                $json = file_get_contents($languageFile);
                $data = json_decode($json, true);

                // Add keys
                foreach ($data as $key => $value) {
                    if (!in_array($key, $keys)) {
                        $keys[] = $key;
                    }

                    $x = array_search($key, $keys)+1;
                    $rows[$x][0] = $key;
                    $rows[$x][$y] = ($value == $key ? "" : $value);
                }

                $headers[$y] = $language;
            }
        }

        // print_r($headers);
        // print_r($rows);

        // Create translation.csv
        $transFile = resource_path('lang/translations.csv');
        $fp = fopen($transFile, 'w');

        // add BOM to fix UTF-8 in Excel
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($fp, $bom);
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        $this->info("Translations exportation completed!");
        $this->info("File exported to : ${transFile}");
        $this->line("");
    }
}
