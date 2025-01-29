<?php

namespace App\Services\TranslationService\Traits;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\TranslationService\Models\TranslationValue;
use App\Services\TranslationService\TranslationService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Lang;
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
        return $this->translation_values()
            ->where('key', $key)
            ->where('locale', $locale)
            ->where('from', $from)
            ->first()
            ?->to;
    }

    /**
     * Translate the specified columns.
     *
     * @param array $columns Columns to translate.
     * @param string|null $sourceLocale The source locale.
     * @param string|null $targetLocale The target locale.
     * @return array Translated columns.
     */
    public function translateColumns(
        array $columns,
        ?string $sourceLocale = null,
        ?string $targetLocale = null,
    ): array {
        // Resolve translation service and determine locales
        $service = $this->getTranslationService();
        $sourceLocale = $sourceLocale ?? $service->getSourceLanguage();
        $targetLocale = $service->getTranslationsMapValue($targetLocale ?? Lang::getLocale());
        $implementation = Implementation::active() ?: Implementation::general();

        $translatedColumns = [];
        $columnsToTranslate = [];
        $newlyTranslatedColumns = [];

        if ($this->isGeneralImplementationOrNotWebshop($implementation)) {
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
        if ($this->shouldSkipTranslation($implementation)) {
            return [...$columns, ...$translatedColumns];
        }

        foreach ($columns as $key => $sourceValue) {
            $newlyTranslatedColumns[$key] = $sourceValue ?
                $this->findTranslatedValue($key, $sourceValue, $sourceLocale, $targetLocale) :
                $sourceValue;
        }

        // Translate only the missing columns
        return [
            ...$translatedColumns,
            ...$newlyTranslatedColumns,
        ];
    }

    /**
     * Check if translation should be skipped.
     *
     * @param Implementation $implementation
     * @return bool
     */
    private function shouldSkipTranslation(Implementation $implementation): bool
    {
        $organization = $implementation->organization;
        $service = $this->getTranslationService();

        // If translations are not allowed or translations are disabled for the organization, skip translation
        if (!$organization || !$organization->allow_translations || !$organization->translations_enabled) {
            return true;
        }

        // Check daily limit
        if (!$organization->translations_daily_limit ||
            $service->getTranslationsTodayCount($organization) >= $organization->translations_daily_limit) {
            return true;
        }

        // Check weekly limit
        if (!$organization->translations_weekly_limit ||
            $service->getTranslationsThisWeekCount($organization) >= $organization->translations_weekly_limit) {
            return true;
        }

        // Check monthly limit
        if (!$organization->translations_monthly_limit ||
            $service->getTranslationsThisMonthCount($organization) >= $organization->translations_monthly_limit) {
            return true;
        }

        return false;
    }

    /**
     * Check if translation should be skipped because it's not a webshop.
     *
     * @param Implementation $implementation
     * @return bool
     */
    private function isGeneralImplementationOrNotWebshop(Implementation $implementation): bool
    {
        // Skip translation for general implementations or non-webshop requests
        return $implementation->isGeneral() || !BaseFormRequest::createFromBase(request())->isWebshop();
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
    ): string {
        // Check if translation already exists
        $existingTranslation = $this->getTranslationValue($key, $targetLocale, $sourceValue);
        $translationService = $this->getTranslationService();

        if ($existingTranslation) {
            return $existingTranslation;
        }

        try {
            if ($sourceValue && $sourceLocale !== $targetLocale) {
                $translatedValue = $translationService
                    ->translateText($sourceValue, $sourceLocale, $targetLocale);

                $this->storeColumnTranslation($key, $sourceValue, $translatedValue, $targetLocale);

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
     * @return void
     */
    private function storeColumnTranslation(
        string $key,
        string $sourceValue,
        string $translatedValue,
        string $locale,
    ): void {
        $implementation = Implementation::active() ?: Implementation::general();

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
