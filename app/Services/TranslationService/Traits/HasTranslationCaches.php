<?php

namespace App\Services\TranslationService\Traits;

use App\Services\TranslationService\Models\TranslationCache;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @extends Eloquent
 */
trait HasTranslationCaches
{
    /**
     * Define a morphMany relationship for translation caches.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translation_caches(): MorphMany
    {
        return $this->morphMany(TranslationCache::class, 'translatable');
    }

    /**
     * Get the translation cache for a given key and locale.
     *
     * @param string $key
     * @param string $locale
     * @return string|null
     */
    public function getCachedTranslation(string $key, string $locale): ?string
    {
        return $this->translation_caches()
            ->where('key', $key)
            ->where('locale', $locale)
            ->first()
            ?->value;
    }

    /**
     * Set the translation cache for a given key and locale.
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     * @return void
     */
    public function cacheTranslation(string $key, string $value, string $locale): void
    {
        $this->translation_caches()->updateOrCreate(
            ['key' => $key, 'locale' => $locale],
            ['value' => $value]
        );
    }

    /**
     * Set the translations cache for a given key and locale.
     *
     * @param array $columns
     * @param string $locale
     * @return void
     */
    public function cacheTranslations(array $columns, string $locale): void
    {
        foreach ($columns as $key => $value) {
            $this->cacheTranslation($key, $value, $locale);
        }
    }
}
