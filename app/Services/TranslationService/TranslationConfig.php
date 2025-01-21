<?php

namespace App\Services\TranslationService;

class TranslationConfig
{
    private string $sourceLanguage;
    private array $targetLanguages;
    private string $provider;
    private array $modelConfigs;
    private array $translationsMap;

    public function __construct()
    {
        $this->sourceLanguage = config('translations.source_language', 'en');
        $this->targetLanguages = config('translations.target_languages', []);
        $this->provider = config('translations.provider', 'debug');
        $this->modelConfigs = config('translations.models', []);
        $this->translationsMap = config('translations.translations_map', []);
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModelConfigs(): array
    {
        return $this->modelConfigs;
    }

    public function getColumnsForModel(string $model): ?array
    {
        return $this->modelConfigs[$model] ?? null;
    }

    /**
     * @param $key
     * @return string
     */
    public function getTranslationsMapValue($key): string
    {
        return $this->translationsMap[$key] ?? $key;
    }
}
