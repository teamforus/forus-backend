<?php

namespace App\Services\TranslationService\Providers;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\TranslationProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class DeepLTranslationProvider implements TranslationProviderInterface
{
    /**
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) Config::get('translation-service.deepl.api_key');

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('DeepL API key is missing in the configuration.');
        }
    }

    /**
     * Translate text using DeepL API.
     *
     * @param string $text The text to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @throws TranslationException
     * @throws ConnectionException
     * @return string The translated text
     */
    public function translate(string $text, string $source, string $target): string
    {
        // Retry 3 times with a 1-second delay between retries
        $response = Http::retry(3, 200)
            ->withHeaders([
                'Authorization' => "DeepL-Auth-Key $this->apiKey",
            ])
            ->post('https://api.deepl.com/v2/translate', [
                'text' => [$text],
                'source_lang' => strtoupper($source),
                'target_lang' => strtoupper($target),
                'tag_handling' => 'html',
            ]);

        if ($response->failed()) {
            $errorMessage = $response->json('message', 'Unknown error');
            $errorMessage = "DeepL API error - request failed - $errorMessage";

            Log::channel('deepl')->error("DeepL API error: $errorMessage");
            throw new TranslationException("Translation failed: $errorMessage");
        }

        try {
            return $response->json('translations.0.text');
        } catch (Throwable $e) {
            $errorMessage = "DeepL API error - failed to extract translated value: {$e->getMessage()}";

            Log::channel('deepl')->error($errorMessage);
            throw new TranslationException($errorMessage);
        }
    }
}
