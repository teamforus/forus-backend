<?php

namespace App\Services\TranslationService;

use Illuminate\Support\Facades\Config;

class TranslationConfig
{
    private string $sourceLanguage;
    private array $targetLanguages;
    private string $provider;
    private array $modelConfigs;
    private array $translationsMap;

    public function __construct()
    {
        $this->sourceLanguage = Config::get('translation-service.source_language', 'en');
        $this->targetLanguages = Config::get('translation-service.target_languages', []);
        $this->provider = Config::get('translation-service.provider', 'debug');
        $this->modelConfigs = Config::get('translation-service.models', []);
        $this->translationsMap = Config::get('translation-service.translations_map', []);
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
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return array
     */
    public function getModelConfigs(): array
    {
        return $this->modelConfigs;
    }

    /**
     * @param string $model
     * @return array|null
     */
    public function getColumnsForModel(string $model): ?array
    {
        return $this->modelConfigs[$model] ?? null;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getTranslationsMapValue(string $key): string
    {
        return $this->translationsMap[$key] ?? $key;
    }
}
