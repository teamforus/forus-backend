<?php

namespace App\Services\TranslationService\Console;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Config;

class TranslateStaticCommand extends Command
{
    protected $signature = 'translate:static {action}';
    protected $description = 'Translate static files using TranslationService';

    /**
     * @param TranslationService $service
     */
    public function __construct(private readonly TranslationService $service)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        try {
            match ($this->argument('action')) {
                'prepare' => $this->service->prepareStatic(),
                'translate' => $this->translateWithProgressBar(),
                'apply' => $this->service->applyStatic(),
                'update-cache' => $this->service->updateStaticCache(),
                'auto' => $this->auto(),
                default => $this->error('Invalid action. Use prepare, translate, apply, or update-cache.'),
            };
        } catch (\Throwable $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }

    /**
     * @return void
     * @throws FileNotFoundException
     * @throws TranslationException
     */
    private function auto(): void
    {
        $this->service->prepareStatic();
        $this->translateWithProgressBar();
        $this->service->applyStatic();
        $this->service->updateStaticCache();
    }

    /**
     * @throws TranslationException
     * @throws FileNotFoundException
     */
    private function translateWithProgressBar(): void
    {
        $addedKeys = $this->service->readCache('cache_added.json');
        $totalKeys = count($addedKeys);
        $targetLangs = $this->service->getTargetLanguages();

        if ($totalKeys === 0) {
            $this->info('No new or updated keys to translate.');
            return;
        }

        $totalSymbols = array_reduce($addedKeys, fn($carry, $item) => $carry + mb_strlen($item), 0);
        $this->info("Found $totalKeys keys and $totalSymbols symbols to translate.");

        foreach ($targetLangs as $locale) {
            $this->info("Translating to $locale...");

            $progressBar = $this->output->createProgressBar($totalKeys);
            $progressBar->start();

            $translations = [];
            $batchSize = Config::get('translation-service.deepl.batch_size');

            $keys = array_keys($addedKeys);
            $values = array_values($addedKeys);

            for ($i = 0; $i < $totalKeys; $i += $batchSize) {
                $batchKeys = array_slice($keys, $i, $batchSize);
                $batchValues = array_slice($values, $i, $batchSize);
                $batchValuesReplaced = array_map(fn ($row) => $this->replaceVars($row), $batchValues);

                $translatedBatch = $this->service->translateBatch(
                    array_pluck($batchValuesReplaced, 'text'),
                    $this->service->getSourceLanguage(),
                    $locale,
                );

                foreach (array_keys($translatedBatch) as $key) {
                    $translatedBatch[$key] = $this->replaceVarsBack(
                        $translatedBatch[$key],
                        $batchValuesReplaced[$key]['replacements'],
                    );
                }

                foreach ($batchKeys as $index => $key) {
                    $translations[$key] = $translatedBatch[$index] ?? '';
                }

                $progressBar->advance(min($batchSize, $totalKeys - $i));
            }

            $progressBar->finish();
            $this->newLine();
            $this->service->writeStaticCache("cache_translated_$locale.json", $translations);
        }
    }

    /**
     * @param string $text
     * @return array
     */
    protected function replaceVars(string $text): array
    {
        preg_match_all('/:([a-zA-Z_]+)/', $text, $matches);

        $placeholders = $matches[0];
        $replacements = [];

        foreach ($placeholders as $index => $placeholder) {
            $replacements["__PLACEHOLDER_{$index}__"] = $placeholder;
        }

        $processedText = str_replace($placeholders, array_keys($replacements), $text);

        return [
            'text' => $processedText,
            'replacements' => $replacements,
        ];
    }

    /**
     * @param string $translatedText
     * @param array $replacements
     * @return string
     */
    protected function replaceVarsBack(string $translatedText, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $translatedText);
    }

}
