<?php

namespace App\Services\TranslationService\Console;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

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

            foreach ($addedKeys as $key => $value) {
                $translations[$key] = $this->service->translateText($value, $this->service->getSourceLanguage(), $locale);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
            $this->service->writeStaticCache("cache_translated_$locale.json", $translations);
        }
    }
}
