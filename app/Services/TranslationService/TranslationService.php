<?php

namespace App\Services\TranslationService;

use App\Models\Organization;
use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\Models\TranslationValue;
use App\Services\TranslationService\Providers\TranslationProvider;
use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Contracts\Translatable;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TranslationService
{
    private string $sourceLanguage;
    private array $targetLanguages;
    private TranslationProvider $provider;
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
     * @return TranslationConfig
     */
    public function getConfig(): TranslationConfig
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getTranslationsMapValue(string $key): string
    {
        return $this->config->getTranslationsMapValue($key);
    }

    /**
     * @return array
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param Model $model The model to translate.
     * @throws TranslationException
     */
    public function translate(Model $model): void
    {
        $this->translateBatchModels([$model]);
    }

    /**
     * Translate a batch of models at once.
     *
     * @param Collection|Model[] $models The models to translate.
     * @throws TranslationException
     */
    public function translateBatchModels(Collection|Arrayable $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $modelClass = $models->first()::class;
        $columns = $this->config->getColumnsForModel($modelClass);
        $translationsToProcess = [];

        foreach ($models as $model) {
            /** @var HasTranslationCaches|Translatable $model */
            $translatedModel = $model->getTranslation($this->sourceLanguage);

            foreach ($columns as $column) {
                $sourceText = $translatedModel[$column] ?? '';
                $existing = $model->getCachedTranslation($column, $this->sourceLanguage);

                if ($existing && $existing === $sourceText) {
                    continue;
                }

                if (!empty($sourceText)) {
                    foreach ($this->targetLanguages as $targetLanguage) {
                        $translationsToProcess[$targetLanguage][$model->getKey()][$column] = $sourceText;
                    }
                }
            }
        }

        foreach ($translationsToProcess as $targetLanguage => $modelsToTranslate) {
            $textBatch = [];

            foreach ($modelsToTranslate as $columns) {
                foreach ($columns as $text) {
                    $textBatch[] = $text;
                }
            }

            $translatedTexts = $this->translateBatch($textBatch, $this->sourceLanguage, $targetLanguage);
            $textIndex = 0;

            foreach ($modelsToTranslate as $modelId => $columns) {
                $translatedColumns = [];
                /** @var Model|HasTranslationCaches $model */
                $model = $models->firstWhere('id', $modelId);
                $columnsToCache = [];

                foreach ($columns as $column => $text) {
                    $index = $textIndex++;
                    $textValue = $translatedTexts[$index] ?? '';
                    $sourceValue = $textBatch[$index] ?? '';

                    $columnsToCache[$column] = $sourceValue;
                    Arr::set($translatedColumns, "$targetLanguage.$column", $textValue);
                }

                $model->update($translatedColumns);
                $model->cacheTranslations($columnsToCache, $this->sourceLanguage);
            }
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
            $translationsPath = resource_path("lang/$locale.json");

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
                $localeMap = $this->getTranslationsMapValue($locale);
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

    /**
     * @throws TranslationException
     */
    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale): array
    {
        return $this->provider->translateBatch($texts, $sourceLocale, $targetLocale);
    }

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        return Log::channel('translate-service');
    }

    /**
     * Get the count of translations today.
     *
     * @param Organization $organization
     * @return int
     */
    public function getTranslationsTodayCount(Organization $organization): int
    {
        return $this->getTranslationsCount($organization, now()->startOfDay(), now()->endOfDay());
    }

    /**
     * Get the count of translations this week.
     *
     * @param Organization $organization
     * @return int
     */
    public function getTranslationsThisWeekCount(Organization $organization): int
    {
        return $this->getTranslationsCount($organization, now()->startOfWeek(), now()->endOfWeek());
    }

    /**
     * Get the count of translations this month.
     *
     * @param Organization $organization
     * @return int
     */
    public function getTranslationsThisMonthCount(Organization $organization): int
    {
        return $this->getTranslationsCount($organization, now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Get the count of translations.
     *
     * @param Organization $organization
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function getTranslationsCount(Organization $organization, Carbon $from, Carbon $to): int
    {
        return TranslationValue::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('created_at', [$from, $to])
            ->sum('from_length');
    }

    /**
     * @param string $provider
     * @return TranslationProvider
     */
    private function createProvider(string $provider): TranslationProvider
    {
        return match ($provider) {
            'deepl' => new Providers\DeepLTranslationProvider(),
            'debug' => new Providers\DebugTranslationProvider(),
            default => throw new InvalidArgumentException("Unsupported translation provider: $provider"),
        };
    }

    /**
     * @param string $locale
     * @return array
     */
    private function getStaticTranslations(string $locale): array
    {
        $localePath = resource_path("lang/$locale");

        if (!File::exists($localePath)) {
            throw new RuntimeException("Translation directory for locale '$locale' not found.");
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
}
