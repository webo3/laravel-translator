## Laravel Translator

A Laravel package that provides artisan commands to scan, export and import translation strings. It extracts translation keys from your source code, manages JSON language files, and supports a CSV-based workflow for working with translators.

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

This creates `config/webo3-translator.php` where you define your languages:

```php
return [
    'languages' => [
        'en',
        'fr',
    ]
];
```

## Usage

### 1. Scan and extract translations

Scans `*.php`, `*.js`, `*.ts` and `*.vue` files in `app/` and `resources/` for translation function calls. Extracts unique keys and creates/updates a JSON file for each configured language at `resources/lang/{language}.json`.

```bash
php artisan translations:scan
```

- New keys are added with the key itself as the default value
- Existing translations are preserved
- Keys are sorted alphabetically
- Comments (`//` and `/* */`) are stripped before scanning, but `//` inside strings (e.g. URLs) is preserved

### 2. Export translations to CSV

Reads all language JSON files and exports them to a single CSV file at `resources/lang/translations.csv`. The CSV includes a UTF-8 BOM for Excel compatibility.

```bash
php artisan translations:export
```

The CSV format:

| key | en | fr |
| --- | --- | --- |
| Hello world | Hello world | Bonjour le monde |
| Goodbye | Goodbye | Au revoir |

Untranslated values (where the value equals the key) appear as empty cells in the CSV.

### 3. Import translations from CSV

Reads `resources/lang/translations.csv` and merges the translated values back into the JSON language files.

```bash
php artisan translations:import
```

- New keys from the CSV are added
- Existing translations are updated if the CSV has a different value
- Empty cells default to the key itself
- Keys are sorted alphabetically after import

### Typical workflow

1. Write code using `__('key')`, `$t('key')`, etc.
2. Run `php artisan translations:scan` to extract all keys
3. Run `php artisan translations:export` to generate the CSV
4. Send the CSV to your translator
5. Place the translated CSV back at `resources/lang/translations.csv`
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
