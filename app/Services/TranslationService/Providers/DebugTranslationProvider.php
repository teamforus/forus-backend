<?php

namespace App\Services\TranslationService\Providers;

use App\Services\TranslationService\TranslationProviderInterface;

class DebugTranslationProvider implements TranslationProviderInterface
{
    /**
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return string The translated text
     */
    public function translate(string $text, string $source, string $target): string
    {
        return "$target: $text";
    }
}
