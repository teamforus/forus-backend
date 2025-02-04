<?php

namespace App\Services\TranslationService\Providers;

use App\Services\TranslationService\Exceptions\TranslationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

abstract class TranslationProvider
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
    abstract public function translate(string $text, string $source, string $target): string;

    /**
     * Translate multiple texts in a batch request.
     *
     * @param array $texts An array of texts to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return array The translated texts, in the same order as input
     * @throws TranslationException
     */
    abstract public function translateBatch(array $texts, string $source, string $target): array;

    /**
     * @param array $data
     * @return void
     */
    protected function log(array $data): void
    {
        if (Config::get('translation-service.log_translations')) {
            Log::channel(Config::get('translation-service.log_channel'))->debug(json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
