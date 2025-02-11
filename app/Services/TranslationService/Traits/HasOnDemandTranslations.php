<?php

namespace App\Services\TranslationService\Traits;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\TranslationService\Models\TranslationValue;
use App\Services\TranslationService\TranslationService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Mews\Purifier\Facades\Purifier;
use Throwable;

/**
 * Trait for handling on-demand translations.
 */
trait HasOnDemandTranslations
{
    /**
     * Define a morphMany relationship for translation caches.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translation_values(): MorphMany
    {
        return $this->morphMany(TranslationValue::class, 'translatable');
    }

    /**
     * Get the translation cache for a given key and locale.
     *
     * @param string $key
     * @param string $locale
     * @param string|null $from
     * @return string|null
     */
    public function getTranslationValue(string $key, string $locale, string|null $from): ?string
    {
        $translation = $this->translation_values()->where([
            'key' => $key,
            'from' => $from,
            'locale' => $locale,
        ])->first();

        return $translation?->to ? $this->purifyTranslation($translation?->to) : null;
    }

    /**
     * Translate the specified columns.
     *
     * @param array $columns Columns to translate.
     * @param string|null $sourceLocale The source locale.
     * @param string|null $targetLocale The target locale.
     * @param BaseFormRequest|null $request
     * @return array Translated columns.
     */
    public function translateColumns(
        array $columns,
        ?string $sourceLocale = null,
        ?string $targetLocale = null,
        BaseFormRequest $request = null,
    ): array {
        // Resolve translation service and determine locales
        $service = $this->getTranslationService();
        $request = $request ?: BaseFormRequest::createFromBase(request());
        $sourceLocale = $sourceLocale ?? $service->getSourceLanguage();
        $targetLocale = $service->getTranslationsMapValue($targetLocale ?? Lang::getLocale());
        $implementation = $request->implementation() ?: Implementation::general();

        $translatedColumns = [];
        $columnsToTranslate = [];
        $newlyTranslatedColumns = [];

        if ($implementation->isGeneral() || !$request->isWebshop() || $sourceLocale === $targetLocale) {
            return $columns;
        }

        // First, check if translations already exist
        foreach ($columns as $key => $value) {
            $existingTranslation = $this->getTranslationValue($key, $targetLocale, $value);

            if ($existingTranslation) {
                $translatedColumns[$key] = $existingTranslation;
            } else {
                $columnsToTranslate[$key] = $value;
            }
        }

        // If all translations exist, return them without checking limits
        if (empty($columnsToTranslate)) {
            return $translatedColumns;
        }

        // Now, check if translation should be skipped due to limits or settings
        if ($this->shouldSkipTranslation($columns, $implementation)) {
            return [...$columns, ...$translatedColumns];
        }

        foreach ($columns as $key => $sourceValue) {
            $newlyTranslatedColumns[$key] = $sourceValue ?
                $this->findTranslatedValue($key, $sourceValue, $sourceLocale, $targetLocale, $implementation) :
                $sourceValue;
        }

        // Translate only the missing columns
        return [
            ...$translatedColumns,
            ...$newlyTranslatedColumns,
        ];
    }

    /**
     * @param string $translation
     * @return string
     */
    protected function purifyTranslation(string $translation): string
    {
        return Purifier::clean($translation, Config::get('forus.purifier.purifier_html_config'));
    }

    /**
     * Check if translation should be skipped.
     *
     * @param array $columns
     * @param Implementation $implementation
     * @return bool
     */
    private function shouldSkipTranslation(array $columns, Implementation $implementation): bool
    {
        $service = $this->getTranslationService();
        $organization = $implementation->organization;

        $daily_limit = $organization->translations_daily_limit;
        $weekly_limit = $organization->translations_weekly_limit;
        $monthly_limit = $organization->translations_monthly_limit;

        $columns_str_len = array_sum(array_map(fn($value) => strlen((string) $value), $columns));

        // If translations are not allowed or translations are disabled for the organization, skip translation
        if (!$organization || !$organization->allow_translations || !$organization->translations_enabled) {
            return true;
        }

        // Check daily limit
        if (!$daily_limit ||
            ($service->getTranslationsTodayCount($organization) + $columns_str_len) > $daily_limit) {
            return true;
        }

        // Check weekly limit
        if (!$weekly_limit ||
            ($service->getTranslationsThisWeekCount($organization) + $columns_str_len) > $weekly_limit) {
            return true;
        }

        // Check monthly limit
        if (!$monthly_limit ||
            ($service->getTranslationsThisMonthCount($organization) + $columns_str_len) > $monthly_limit) {
            return true;
        }

        return false;
    }

    /**
     * Find the translated value for a column.
     *
     * @param string $key Column key.
     * @param string $sourceValue Original value.
     * @param string $sourceLocale The source locale.
     * @param string $targetLocale The target locale.
     * @return string Translated value.
     */
    private function findTranslatedValue(
        string $key,
        string $sourceValue,
        string $sourceLocale,
        string $targetLocale,
        Implementation $implementation,
    ): string {
        // Check if translation already exists
        $existingTranslation = $this->getTranslationValue($key, $targetLocale, $sourceValue);
        $translationService = $this->getTranslationService();

        if ($existingTranslation) {
            return $existingTranslation;
        }

        try {
            if ($sourceValue && $sourceLocale !== $targetLocale) {
                $translatedValue = $translationService->translateText($sourceValue, $sourceLocale, $targetLocale);
                $translatedValue = $this->purifyTranslation($translatedValue);

                $this->storeColumnTranslation($key, $sourceValue, $translatedValue, $targetLocale, $implementation);

                return $translatedValue;
            }
        } catch (Throwable $e) {
            $translationService->logger()->error('Translation failed: ' . $e->getMessage());
        }

        return $sourceValue;
    }

    /**
     * Store a new translation in the database.
     *
     * @param string $key Column key.
     * @param string $sourceValue Original value.
     * @param string $translatedValue Translated value.
     * @param string $locale The locale of the translation.
     * @param Implementation $implementation
     * @return void
     */
    private function storeColumnTranslation(
        string $key,
        string $sourceValue,
        string $translatedValue,
        string $locale,
        Implementation $implementation,
    ): void {
        $this->translation_values()->create([
            'key' => $key,
            'from' => $sourceValue,
            'from_length' => mb_strlen($sourceValue),
            'to' => $translatedValue,
            'to_length' => mb_strlen($translatedValue),
            'locale' => $locale,
            'implementation_id' => $implementation->id,
            'organization_id' => $implementation->organization_id,
        ]);
    }

    /**
     * Get the TranslationService instance.
     *
     * @return TranslationService
     */
    private function getTranslationService(): TranslationService
    {
        return resolve(TranslationService::class);
    }
}
