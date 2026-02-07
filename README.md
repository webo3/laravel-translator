## Laravel Translator

A Laravel package that provides artisan commands to scan, export and import translation strings. It extracts translation keys from your source code, manages JSON language files, and supports CSV and XLIFF workflows for working with translators.

### Supported translation functions

| Function | File types | Example |
| --- | --- | --- |
| `__()` | PHP, JS, TS, Vue | `__('Hello world')` |
| `Lang::get()` | PHP | `Lang::get('messages.welcome')` |
| `@lang()` | PHP (Blade) | `@lang('Please login')` |
| `$t()` | JS, TS, Vue | `$t('Vue key')` |
| `.t()` | JS, TS, Vue | `i18n.t('My key')` |

### Quote handling

- Single quotes: `__('key')`
- Double quotes: `__("key")`
- Escaped quotes: `__('It\'s a test')` correctly extracts `It's a test`
- Backtick template literals (JS/TS/Vue only): `` __(`multi-line key`) ``
- Backticks are ignored in PHP files (where they are the shell execution operator)

### Installation

```bash
composer require webo3/laravel-translator
```

Package auto-discovery is enabled for Laravel >= 5.5. For older versions, register the provider manually in `config/app.php`:

```php
'providers' => [
    webO3\Translator\Providers\TranslatorServiceProvider::class,
],
```

## Configuration

Publish the configuration file to specify which languages to manage:

```bash
php artisan vendor:publish --provider="webO3\Translator\Providers\TranslatorServiceProvider" --tag=config
```

This creates `config/webo3-translator.php` with the following options:

```php
return [
    // Languages to manage
    'languages' => ['en', 'fr'],

    // Where JSON language files are stored
    'lang_path' => resource_path('lang'),

    // Where export files are written (null = same as lang_path)
    'export_path' => null,

    // Directories to scan for translation keys
    'scan_paths' => [app_path(), resource_path()],

    // Export format: 'csv' or 'xliff'
    'format' => 'csv',
];
```

## Usage

### 1. Scan and extract translations

Scans `*.php`, `*.js`, `*.ts` and `*.vue` files in the configured `scan_paths` for translation function calls. Extracts unique keys and creates/updates a JSON file for each configured language.

```bash
php artisan translations:scan
```

- New keys are added with the key itself as the default value
- Existing translations are preserved
- Keys are sorted alphabetically
- Comments (`//` and `/* */`) are stripped before scanning, but `//` inside strings (e.g. URLs) is preserved

### 2. Export translations

Reads all language JSON files and exports them in the configured format.

```bash
php artisan translations:export
```

#### CSV format (default)

Exports a single `translations.csv` file with a UTF-8 BOM for Excel compatibility:

| key | en | fr |
| --- | --- | --- |
| Hello world | Hello world | Bonjour le monde |
| Goodbye | Goodbye | Au revoir |

Untranslated values (where the value equals the key) appear as empty cells.

#### XLIFF format

Set `'format' => 'xliff'` in your config. Exports one XLIFF 1.2 file per target language (e.g. `translations-fr.xlf`). The first language in your `languages` array is used as the source language.

XLIFF is the industry standard format supported by professional translation tools (SDL Trados, memoQ, Phrase, Crowdin, etc.).

### 3. Import translations

Reads the exported file(s) and merges the translated values back into the JSON language files.

```bash
php artisan translations:import
```

- New keys are added
- Existing translations are updated if the file has a different value
- Empty values default to the key itself
- Keys are sorted alphabetically after import
- The import format is determined by the `format` config option (same as export)

### Typical workflow

1. Write code using `__('key')`, `$t('key')`, etc.
2. Run `php artisan translations:scan` to extract all keys
3. Run `php artisan translations:export` to generate translation file(s)
4. Send the file(s) to your translator (CSV or XLIFF)
5. Place the translated file(s) back in the export path
6. Run `php artisan translations:import` to merge translations back

## Using translations in Vue

Install the vue-i18n package:

```bash
npm install vue-i18n --save-dev
```

Set up vue-i18n in your `app.js`:

```js
import { createI18n } from 'vue-i18n';
import en from '../lang/en.json';
import fr from '../lang/fr.json';

const i18n = createI18n({
    locale: 'en',
    messages: { en, fr }
});

app.use(i18n);
```

Then use `$t()` or `i18n.t()` in your templates:

```html
<a :title="$t('Hello world')">{{ $t('Hello world') }}</a>
```

## Testing

```bash
composer test              # Run tests without coverage
composer test-coverage     # Run tests with coverage report
```

## License

MIT
