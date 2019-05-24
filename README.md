## Laravel Translator

The package helps you manage your translations via artisan commands. Package auto-discovery is enabled with Laravel >= 5.5.

### For Laravel < 5.5
You need to register the provider in app.php:
```php
webO3\Translator\TranslatorServiceProvider::class,
```

## Configuration
You need to specify which languages you would like to manage with the extensions.

To create the file config/webo3-translator.php use the following command:
```bash
php artisan vendor:publish --provider=webO3\\Translator\\TranslatorServiceProvider --tag=config
```


### Scanning and extracting translations
The package works by scanning the source code for translations using the function __().

The translations:scan command it will scan for __() function the files in the app and the resources folder with the following extensions : *.php, *.js, *.vue. It will then create a file for each enabled language to resources/lang/{language}.json with all the translations key/values pair.

The JSON file will be loaded by Laravel to translate the strings you use in the source code by default.

To scan and extract the translation, use the following command:
```bash
php artisan translations:scan
```


### Exporting translations
For help with translations, we use a script that will export all JSON content to a .csv file that we could then share in Excel or in its original CSV format. In the file, we will have multiple columns, one for the key and one by language. The key is the string used in our __() function. If the translations don't exist, it will use it instead. Here's an example if the French and English languages are enabled in our configuration file.

| key | en | fr |
| --- | --- | --- |
| This is an example key. | This is an example key. | Ceci est un exemple de clés. |
| This key as an error. | This key has an error. | Cette clé à une erreur. |

When we ship this file to our translator, we need to specify to them to not touch the key column, that our reference for translating. If they are an error in the key it doesn't matter, just correct it in the language column.

To create the translations.csv file use the following command:
```bash
php artisan translations:export
```

### Importing translations
Now that we have translated the CSV file, put it back and import it with the following command :

```bash
php artisan translations:import
```


## Using translations file in Vue
To be able to use the JSON file in Vue we need to add a NPM package. (vue-i18n).

```bash
npm install vue-i18n --save-dev
```

Add the following in the app.js file.
```js
// Load translations
let text_fr = require('../lang/fr.json');
let text_en = require('../lang/en.json');

// Define the current locale
let locale = 'en';

// Init VueI18n
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);
window.i18n = new VueI18n({
    locale: locale,
    silentTranslationWarn: true,
    messages: {
        "en": text_en,
        "fr": text_fr
    }
});
```

It will then be possible to use the VueI18n to translate our VueJs template using the functions $t() or i18n.t();
```js
i18n.t('This is an example key.')
```

Or in the vue template :
```html
<a :title="$t('This is an example key.')">{{ $t('This is an example key.') }}</a>
```