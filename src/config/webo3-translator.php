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

];
