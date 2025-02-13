<?php

namespace App\Services\TranslationService\Providers;

use App\Services\TranslationService\Exceptions\TranslationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class DeepLTranslationProvider extends TranslationProvider
{
    /**
     * @var string|null
     */
    protected ?string $apiKey;
    protected ?bool $apiFree;

    public function __construct()
    {
        $this->apiKey = (string) Config::get('translation-service.deepl.api_key');
        $this->apiFree = (bool) Config::get('translation-service.deepl.free');

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
        $this->log([
            'type' => 'single',
            'source' => $source,
            'target' => $target,
            'text' => $text,
        ]);

        return $this->translateBatch([$text], $source, $target)[0] ?? '';
    }

    /**
     * Translate an array of texts in a single batch request using DeepL API.
     *
     * @param array $texts The array of texts to be translated
     * @param string $source The source language
     * @param string $target The target language
     * @throws TranslationException
     * @throws ConnectionException
     * @return array The translated texts in the same order as input
     */
    public function translateBatch(array $texts, string $source, string $target): array
    {
        $this->log([
            'type' => 'batch',
            'source' => $source,
            'target' => $target,
            'texts' => $texts,
        ]);

        // Filter out empty or non-string values
        $validEntries = [];
        $originalIndices = [];

        $apiUrl = $this->apiFree ?
            'https://api-free.deepl.com/v2/translate' :
            'https://api.deepl.com/v2/translate';

        foreach ($texts as $index => $text) {
            if (is_string($text) && trim($text) !== '') {
                $validEntries[] = $text;
                $originalIndices[] = $index;
            }
        }

        // If no valid entries exist, return an array of empty strings
        if (empty($validEntries)) {
            return array_fill(0, count($texts), '');
        }

        // Send batch request to DeepL
        $response = Http::retry(3, 200)
            ->withHeaders([
                'Authorization' => "DeepL-Auth-Key $this->apiKey",
            ])
            ->post($apiUrl, [
                'text' => $validEntries,
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
            $translations = $response->json('translations', []);

            if (!is_array($translations)) {
                throw new TranslationException('DeepL API response is not an array.');
            }

            // Create an output array initialized with empty strings
            $translatedTexts = array_fill(0, count($texts), '');

            // Map translated results back to their original positions
            foreach ($originalIndices as $resultIndex => $originalIndex) {
                $translatedTexts[$originalIndex] = $translations[$resultIndex]['text'] ?? '';
            }

            return $translatedTexts;
        } catch (Throwable $e) {
            $errorMessage = "DeepL API error - failed to extract translated values: {$e->getMessage()}";

            Log::channel('deepl')->error($errorMessage);
            throw new TranslationException($errorMessage);
        }
    }
}
