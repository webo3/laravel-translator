<?php

namespace webO3\Translator\Translators;

use RuntimeException;

class GoogleFreeTranslateDriver implements TranslatorDriverInterface
{
    public function translate($text, $from, $to)
    {
        $results = $this->translateBatch([$text], $from, $to);
        return $results[0];
    }

    public function translateBatch(array $texts, $from, $to)
    {
        if (!class_exists(\Stichoza\GoogleTranslate\GoogleTranslate::class)) {
            throw new RuntimeException(
                'The stichoza/google-translate-php package is required for the google_free driver. '
                . 'Install it with: composer require stichoza/google-translate-php'
            );
        }

        $translator = new \Stichoza\GoogleTranslate\GoogleTranslate();
        $translator->setSource($from);
        $translator->setTarget($to);

        $guards = [];
        $masked = [];

        foreach ($texts as $i => $text) {
            $guard = new PlaceholderGuard();
            $guards[$i] = $guard;
            $masked[$i] = $guard->mask($text);
        }

        $results = [];
        foreach ($masked as $i => $text) {
            try {
                $translated = $translator->translate($text);
                $results[$i] = $guards[$i]->unmask($translated);
            } catch (\Exception $e) {
                throw new RuntimeException(
                    'Google Translate API error: ' . $e->getMessage(), 0, $e
                );
            }
        }

        return $results;
    }
}
