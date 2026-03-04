<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | This is an array of languages that the translator will use to
    | generate the translations.
    |
    */

    'languages' => [
        'en',  // First language is the default one, used as source for translations.
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Path
    |--------------------------------------------------------------------------
    |
    | The directory where JSON language files (en.json, fr.json, etc.) are
    | stored. Defaults to resources/lang.
    |
    */

    'lang_path' => resource_path('lang'),

    /*
    |--------------------------------------------------------------------------
    | Export Path
    |--------------------------------------------------------------------------
    |
    | The directory where export files (CSV or XLIFF) are written to and
    | imported from. Set to null to use the same directory as lang_path.
    |
    */

    'export_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for translation keys when running translations:scan.
    |
    */

    'scan_paths' => [
        app_path(),
        resource_path(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Format
    |--------------------------------------------------------------------------
    |
    | The file format used for export and import. Supported: 'csv', 'xliff'.
    |
    */

    'format' => 'csv',

    /*
    |--------------------------------------------------------------------------
    | Translation Driver
    |--------------------------------------------------------------------------
    |
    | The driver used by translations:translate for auto-translation.
    | Supported: 'google_free'
    |
    | google_free: Uses stichoza/google-translate-php (no API key required).
    |              Install with: composer require stichoza/google-translate-php
    |
    */

    'translation_driver' => 'google_free',

    /*
    |--------------------------------------------------------------------------
    | Translation Driver Options
    |--------------------------------------------------------------------------
    |
    | Driver-specific options. Reserved for future drivers that may require
    | API keys or custom endpoints.
    |
    */

    'translation_options' => [
        // 'api_key' => env('TRANSLATION_API_KEY'),
    ],

];
