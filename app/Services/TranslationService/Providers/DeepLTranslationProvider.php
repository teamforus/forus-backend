<?php

namespace App\Services\TranslationService\Providers;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\TranslationProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DeepLTranslationProvider implements TranslationProviderInterface
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('translations.deepl.api_key');

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException("DeepL API key is missing in the configuration.");
        }
    }

    /**
     * Translate text using DeepL API
     *
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @return string The translated text
     * @throws TranslationException
     * @throws ConnectionException
     */
    public function translate(string $text, string $source, string $target): string
    {
        $response = Http::withHeaders([
            'Authorization' => "DeepL-Auth-Key $this->apiKey",
        ])->post('https://api.deepl.com/v2/translate', [
            'text' => [$text],
            'source_lang' => strtoupper($source),
            'target_lang' => strtoupper($target),
        ]);

        if ($response->failed()) {
            $errorMessage = $response->json('message', 'Unknown error');
            $errorMessage = "DeepL API error - request failed - $errorMessage";

            Log::channel('deepl')->error("DeepL API error: $errorMessage");
            throw new TranslationException("Translation failed: $errorMessage");
        }

        try {
            return $response->json('translations.0.text');
        } catch (\Exception $e) {
            $errorMessage = "DeepL API error - failed to extract translated value: {$e->getMessage()}";

            Log::channel('deepl')->error($errorMessage);
            throw new TranslationException($errorMessage);
        }
    }
}

