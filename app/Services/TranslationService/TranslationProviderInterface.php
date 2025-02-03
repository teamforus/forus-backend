<?php

namespace App\Services\TranslationService;

use App\Services\TranslationService\Exceptions\TranslationException;

interface TranslationProviderInterface
{
    /**
     * Translate a single text string.
     *
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return string The translated text
     * @throws TranslationException
     */
    public function translate(string $text, string $source, string $target): string;

    /**
     * Translate multiple texts in a batch request.
     *
     * @param array $texts An array of texts to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return array The translated texts, in the same order as input
     * @throws TranslationException
     */
    public function translateBatch(array $texts, string $source, string $target): array;
}
