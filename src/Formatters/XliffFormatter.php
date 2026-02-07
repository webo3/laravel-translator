<?php

namespace webO3\Translator\Formatters;

class XliffFormatter implements FormatterInterface
{
    const XLIFF_NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * {@inheritdoc}
     */
    public function export(array $languages, array $translations, $exportPath)
    {
        // First language in the config is the source language
        $sourceLanguage = $languages[0];
        $sourceTranslations = isset($translations[$sourceLanguage]) ? $translations[$sourceLanguage] : [];

        // Collect all unique keys across all languages
        $allKeys = [];
        foreach ($translations as $langData) {
            foreach (array_keys($langData) as $key) {
                if (!in_array($key, $allKeys, true)) {
                    $allKeys[] = $key;
                }
            }
        }

        $createdFiles = [];

        // One .xlf file per target language
        foreach ($languages as $language) {
            if ($language === $sourceLanguage) {
                continue;
            }

            $targetTranslations = isset($translations[$language]) ? $translations[$language] : [];

            $xml = new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<xliff xmlns="' . self::XLIFF_NS . '" version="1.2"/>'
            );

            $file = $xml->addChild('file');
            $file->addAttribute('source-language', $sourceLanguage);
            $file->addAttribute('target-language', $language);
            $file->addAttribute('datatype', 'plaintext');
            $file->addAttribute('original', 'translations');

            $body = $file->addChild('body');

            $id = 0;
            foreach ($allKeys as $key) {
                $id++;
                $unit = $body->addChild('trans-unit');
                $unit->addAttribute('id', (string) $id);

                // Use property assignment for proper XML escaping
                $source = $unit->addChild('source');
                $source[0] = $key;

                $target = $unit->addChild('target');
                $targetValue = isset($targetTranslations[$key]) ? $targetTranslations[$key] : '';
                $target[0] = $targetValue;
            }

            $filePath = rtrim($exportPath, '/') . '/translations-' . $language . '.xlf';

            // Use DOMDocument for pretty-printing
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $dom->save($filePath);

            $createdFiles[] = $filePath;
        }

        return $createdFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function import($exportPath)
    {
        $results = [];
        $files = glob(rtrim($exportPath, '/') . '/translations-*.xlf');

        foreach ($files as $file) {
            $xml = simplexml_load_file($file);

            // Access elements directly (default namespace applies automatically)
            // Use attributes() to reliably read unnamespaced attributes
            $fileNode = $xml->file;
            $targetLanguage = (string) $fileNode->attributes()['target-language'];

            $translations = [];
            foreach ($fileNode->body->children() as $unit) {
                $source = (string) $unit->source;
                $target = (string) $unit->target;
                $translations[$source] = ($target !== '') ? $target : $source;
            }

            $results[$targetLanguage] = $translations;
        }

        return $results;
    }
}
