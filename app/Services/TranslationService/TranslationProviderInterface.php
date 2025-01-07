<?php

namespace App\Services\TranslationService;

use App\Services\TranslationService\Exceptions\TranslationException;

interface TranslationProviderInterface
{
    /**
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return string The translated text
     * @throws TranslationException
     */
    public function translate(string $text, string $source, string $target): string;
}
