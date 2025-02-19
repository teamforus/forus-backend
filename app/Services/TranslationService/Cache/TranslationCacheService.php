<?php

namespace App\Services\TranslationService\Cache;

use App\Services\TranslationService\TranslationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TranslationCacheService
{
    /**
     * Retrieve a cached translation.
     *
     * @param Model  $model
     * @param string $key
     * @param string $locale
     * @param string|null $from
     * @return string|null
     */
    public function get(Model $model, string $key, string $locale, ?string $from): ?string
    {
        try {
            $translationService = resolve(TranslationService::class);
            $driver = $translationService->getConfig()->getCacheDriver();
            $cacheKey = $this->generateCacheKey($model, $key, $locale, $from);

            return Cache::driver($driver)->get($cacheKey);
        } catch (Throwable $e) {
            $translationService = resolve(TranslationService::class);

            $translationService->logger()->error("Cache get failed for key '$key'", [
                'model' => $model->getMorphClass() . '#' . $model->getKey(),
                'key' => $key,
                'locale' => $locale,
                'from' => $from,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Store a translation in cache.
     *
     * @param Model  $model
     * @param string $key
     * @param string $locale
     * @param string|null $from
     * @param string|null $to
     * @return bool
     */
    public function set(Model $model, string $key, string $locale, ?string $from, ?string $to): bool
    {
        try {
            $translationService = resolve(TranslationService::class);
            $driver = $translationService->getConfig()->getCacheDriver();
            $cacheKey = $this->generateCacheKey($model, $key, $locale, $from);
            $cacheTime = $translationService->getConfig()->getCacheTime();

            return Cache::driver($driver)->put($cacheKey, $to, $cacheTime);
        } catch (Throwable $e) {
            $translationService = resolve(TranslationService::class);

            $translationService->logger()->error("Cache set failed for key '$key'", [
                'model' => $model->getMorphClass() . '#' . $model->getKey(),
                'key' => $key,
                'locale' => $locale,
                'from' => $from,
                'to' => $to,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Generate a unique cache key based on the model and translation parameters.
     *
     * @param Model  $model
     * @param string $key
     * @param string $locale
     * @param string|null $from
     * @return string
     */
    public function generateCacheKey(Model $model, string $key, string $locale, ?string $from): string
    {
        // Concatenate model class, model ID, key, locale, and source value
        $rawKey = implode('.', [
            $model->getMorphClass(),
            $model->getKey(),
            $key,
            $locale,
            $from ?? 'default',
        ]);

        // Generate SHA-256 hash of the concatenated string
        $hashedKey = hash('sha256', $rawKey);

        // Return with a namespace prefix for clarity
        return 'translation.' . $hashedKey;
    }
}
