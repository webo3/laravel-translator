## Laravel Translator

A Laravel package that provides artisan commands to scan, export, import and auto-translate translation strings. It extracts translation keys from your source code, manages JSON language files, supports CSV and XLIFF workflows for working with translators, and can automatically translate using the Google Translate API.

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

    // Translation driver for auto-translate: 'google_free'
    'translation_driver' => 'google_free',

    // Driver-specific options
    'translation_options' => [],
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

### 4. Auto-translate with an API

Automatically translates untranslated keys using a translation API and saves the results directly to your JSON language files.

First, install the Google Translate package:

```bash
composer require stichoza/google-translate-php
```

Then run:

```bash
php artisan translations:translate
```

This translates all untranslated keys (where value equals the key) from the source language to every other configured language.

#### Options

| Option | Description |
| --- | --- |
| `--lang=fr,es` | Only translate specific target languages |
| `--force` | Re-translate keys that already have translations |
| `--driver=google_free` | Override the translation driver from config |
| `--batch-size=50` | Number of strings per API call (default: 50) |

#### Placeholder preservation

The command automatically preserves Laravel and Vue placeholders during translation:

- Laravel style: `:name`, `:count`, `:attribute`
- Brace style: `{name}`, `{count}`, `{item}`

These are masked before sending to the API and restored in the translated result.

#### Case deduplication

Keys that differ only in letter case (e.g. `"Hello"`, `"hello"`, `"HELLO"`) are deduplicated into a single API call. The translated result is then adjusted to match each variant's casing (uppercase, lowercase, title case).

#### Configuration

Two config keys control auto-translation:

```php
// Translation driver: 'google_free'
'translation_driver' => 'google_free',

// Driver-specific options (reserved for future drivers)
'translation_options' => [
    // 'api_key' => env('TRANSLATION_API_KEY'),
],
```

#### Custom drivers

You can register your own translation driver by binding it in the service container:

```php
$this->app->singleton('translation.driver.my_driver', function () {
    return new MyCustomDriver(); // must implement TranslatorDriverInterface
});
```

Then use it with `--driver=my_driver` or set `'translation_driver' => 'my_driver'` in config.

### Typical workflow

1. Write code using `__('key')`, `$t('key')`, etc.
2. Run `php artisan translations:scan` to extract all keys
3. Either:
   - **Auto-translate**: Run `php artisan translations:translate` to translate via API
   - **Manual workflow**: Export → send to translator → import:
     1. `php artisan translations:export`
     2. Send the file(s) to your translator
     3. `php artisan translations:import`

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
