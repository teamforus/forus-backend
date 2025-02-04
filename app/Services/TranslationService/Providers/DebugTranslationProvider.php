<?php

namespace App\Services\TranslationService\Providers;

class DebugTranslationProvider extends TranslationProvider
{
    /**
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return string The translated text
     */
    public function translate(string $text, string $source, string $target): string
    {
        $this->log([
            'type' => 'single',
            'source' => $source,
            'target' => $target,
            'text' => $text,
        ]);

        return "$target: $text";
    }

    /**
     * @param array $texts An array of texts to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return array The translated texts, in the same order as input
     */
    public function translateBatch(array $texts, string $source, string $target): array
    {
        $this->log([
            'type' => 'batch',
            'source' => $source,
            'target' => $target,
            'texts' => $texts,
        ]);

        return array_map(fn($text) => is_string($text) && trim($text) !== '' ? "$target: $text" : '', $texts);
    }
}
