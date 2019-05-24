<?php

namespace webO3\Translator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take the lang/translations.csv file and import it.';

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
        $this->info("Importing texts from lang/translation.csv");

        ini_set('auto_detect_line_endings', true);

        // Open the CSV
        $headers = [];
        $datas = [];
        $updated = 0;
        $added = 0;
        $numLine = 0;
        $statuses = [];

        $transFile = resource_path('lang/translations.csv');
        if (($handle = fopen($transFile, "r")) !== false) {
            $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
            if (fgets($handle, 4) !== $bom) {
                // BOM not found - rewind pointer to start of file.
                rewind($handle);
            }

            while (($line = fgetcsv($handle)) !== false) {
                $numLine++;

                // First line is the header
                if (empty($headers)) {
                    foreach ($line as $key => $value) {
                        $value = trim($value);
                        $key = trim($key);
                        $headers[$value] = $key;
                        if ($value != 'key') {
                            $datas[$value] = [];
                        }
                    }

                } else {

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
        $this->warn("Total languages in the file: ${totalLang}");
        $this->warn("Total line to merge: ${numLine}");

        // Saving to JSON
        foreach ($datas as $language => $texts) {
            $languageFile = resource_path('lang/'.$language.'.json');

            $json = file_get_contents($languageFile);
            $data = json_decode($json, true);

            // Merge arrays
            // New keys
            foreach ($texts as $key => $value) {
                if (!isset($data[$key]) || $data[$key] == '') {
                    $added++;
                    $data[$key] = ($value == '' ? $key : $value);
                }
                if ($data[$key] != $value) {
                    $updated++;
                    $data[$key] = ($value == '' ? $key : $value);
                }
            }

            ksort($data);


            // Write the file
            $json = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($languageFile, $json);

            $statuses[] = [$language, $updated, $added];
        }

        $this->table([
            'Language', 'Updated', 'Added'
        ], $statuses);

        $this->info("Translations importation completed!");
        $this->line("");
    }
}
