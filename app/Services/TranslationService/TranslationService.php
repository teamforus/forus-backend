<?php

namespace App\Services\TranslationService;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\Traits\TranslatableTrait;
use Astrotomic\Translatable\Contracts\Translatable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationService
{
    private string $sourceLanguage;
    private array $targetLanguages;
    private TranslationProviderInterface $provider;
    private TranslationConfig $config;
    private string $cachePath;

    /**
     * @param TranslationConfig $config
     */
    public function __construct(TranslationConfig $config)
    {
        $this->sourceLanguage = $config->getSourceLanguage();
        $this->targetLanguages = $config->getTargetLanguages();
        $this->config = $config;

        // Dynamically load the provider based on config
        $this->provider = $this->createProvider($config->getProvider());
        $this->cachePath = storage_path('translations-caches');

        if (!File::exists($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true);
        }
    }

    /**
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * @return array
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param string $provider
     * @return TranslationProviderInterface
     */
    private function createProvider(string $provider): TranslationProviderInterface
    {
        return match ($provider) {
            'deepl' => new Providers\DeepLTranslationProvider(),
            'debug' => new Providers\DebugTranslationProvider(),
            default => throw new \InvalidArgumentException("Unsupported translation provider: $provider"),
        };
    }

    /**
     * @param object $model The model to translate.
     * @throws TranslationException
     */
    public function translate(object $model): void
    {
        /** @var TranslatableTrait|Translatable $model */
        $modelClass = $model::class;
        $columns = $this->config->getColumnsForModel($modelClass);

        if (!$columns) {
            return;
        }

        $translatedModel = $model->getTranslation($this->sourceLanguage);

        foreach ($columns as $column) {
            $sourceText = $translatedModel[$column];
            $existing = $model->getCachedTranslation($column, $this->sourceLanguage);
            $translatedColumns = [];

            // Skip if source text hasn't changed from last time
            if ($existing && $existing === $sourceText) {
                continue;
            }

            if (!empty($sourceText)) {
                foreach ($this->targetLanguages as $targetLanguage) {
                    $translatedText = $this->translateText(
                        $sourceText,
                        $this->sourceLanguage,
                        $targetLanguage,
                    );

                    Arr::set(
                        $translatedColumns,
                        $this->config->getTranslationsMapValue($targetLanguage) . '.' . $column,
                        $translatedText,
                    );
                }

            }

            $model->update($translatedColumns);
            $model->cacheTranslation($column, $sourceText, $this->sourceLanguage);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function prepareStatic(): void
    {
        $originalTranslations = $this->getStaticTranslations($this->sourceLanguage);
        $cache = $this->readCache('cache.json');

        $addedOrUpdated = [];
        $removedKeys = [];

        foreach ($originalTranslations as $key => $value) {
            if (!isset($cache[$key]) || $cache[$key] !== $value) {
                $addedOrUpdated[$key] = $value;
            }
        }

        foreach ($cache as $key => $value) {
            if (!isset($originalTranslations[$key])) {
                $removedKeys[] = $key;
            }
        }

        $this->writeStaticCache('cache_added.json', $addedOrUpdated);
        $this->writeStaticCache('cache_removed.json', $removedKeys);
    }

    /**
     * @throws FileNotFoundException
     */
    public function applyStatic(): void
    {
        foreach ($this->targetLanguages as $locale) {
            $localeMap = $this->config->getTranslationsMapValue($locale);
            $translationsPath = resource_path("lang/$localeMap.json");

            $existingTranslations = File::exists($translationsPath)
                ? json_decode(File::get($translationsPath), true)
                : [];

            $newTranslations = $this->readCache("cache_translated_$locale.json");
            $mergedTranslations = array_merge($existingTranslations, $newTranslations);

            ksort($mergedTranslations);

            File::put($translationsPath, json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $removedKeys = $this->readCache('cache_removed.json');

        foreach ($removedKeys as $key) {
            foreach ($this->targetLanguages as $locale) {
                $localeMap = $this->config->getTranslationsMapValue($locale);
                $translationsPath = resource_path("lang/$localeMap.json");

                if (File::exists($translationsPath)) {
                    $translations = json_decode(File::get($translationsPath), true);

                    if (isset($translations[$key])) {
                        unset($translations[$key]);
                        File::put($translationsPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
            }
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function updateStaticCache(): void
    {
        // Load existing cache
        $cache = $this->readCache('cache.json');

        // Load added and removed keys
        $addedKeys = $this->readCache('cache_added.json');
        $removedKeys = $this->readCache('cache_removed.json');

        // Remove keys from cache
        foreach ($removedKeys as $key) {
            unset($cache[$key]);
        }

        // Add or update keys in the cache
        foreach ($addedKeys as $key => $value) {
            $cache[$key] = $value;
        }

        ksort($cache);

        // Write updated cache back to file
        $this->writeStaticCache('cache.json', $cache);

        // Clear the added and removed caches
        $this->writeStaticCache('cache_added.json', []);
        $this->writeStaticCache('cache_removed.json', []);
    }

    /**
     * @param string $locale
     * @return array
     */
    private function getStaticTranslations(string $locale): array
    {
        $localePath = resource_path("lang/$locale");

        if (!File::exists($localePath)) {
            throw new \RuntimeException("Translation directory for locale '$locale' not found.");
        }

        $translations = [];

        foreach (File::allFiles($localePath) as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = $file->getRelativePathname();
                $content = include $file->getPathname();

                if (is_array($content)) {
                    $keyPrefix = str_replace(DIRECTORY_SEPARATOR, '.', pathinfo($relativePath, PATHINFO_DIRNAME));
                    $fileKey = pathinfo($relativePath, PATHINFO_FILENAME);

                    // Add prefix if within a subdirectory
                    $keyPrefix = $keyPrefix === '.' ? '' : "$keyPrefix.";

                    // Merge translations under their appropriate keys
                    $translations = array_merge($translations, $this->flattenArray([$keyPrefix . $fileKey => $content]));
                }
            }
        }

        return $translations;
    }

    /**
     * @param array $array
     * @param string $prefix
     * @return array
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : "$prefix.$key";

            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenArray($value, $newKey));
            } else {
                $flattened[$newKey] = $value;
            }
        }

        return $flattened;
    }

    /**
     * @throws FileNotFoundException
     */
    public function readCache(string $fileName): array
    {
        $filePath = $this->cachePath . '/' . $fileName;

        if (!File::exists($filePath)) {
            return [];
        }

        return json_decode(File::get($filePath), true) ?? [];
    }

    /**
     * @param string $fileName
     * @param array $data
     * @return void
     */
    public function writeStaticCache(string $fileName, array $data): void
    {
        $filePath = $this->cachePath . '/' . $fileName;

        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @throws TranslationException
     */
    public function translateText(string $text, string $sourceLocale, string $targetLocale): string
    {
        return $this->provider->translate($text, $sourceLocale, $targetLocale);
    }
}
