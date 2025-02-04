<?php

namespace App\Services\TranslationService\Console;

use App\Services\TranslationService\Exceptions\TranslationException;
use App\Services\TranslationService\TranslationConfig;
use App\Services\TranslationService\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Throwable;

class TranslateCommand extends Command
{
    protected $signature = 'translate {--model=} {--force}';
    protected $description = 'Automatically translate translatable models';

    private TranslationConfig $config;
    private TranslationService $translationService;

    /**
     * Constructor to initialize the command.
     */
    public function __construct(TranslationConfig $config, TranslationService $translationService)
    {
        parent::__construct();

        $this->config = $config;
        $this->translationService = $translationService;
    }

    /**
     * Execute the translation command.
     *
     * @return void
     */
    public function handle(): void
    {
        $models = $this->getModels();
        $batchSize = Config::get('translation-service.deepl.batch_size');

        foreach ($models as $modelClass) {
            $this->info('[' . Str::padRight($modelClass, 78, '_') . ']');

            $modelInstances = $modelClass::all();
            $totalModels = $modelInstances->count();

            if ($totalModels === 0) {
                $this->info('No records found for translation.');
                continue;
            }

            $this->withProgressBar($totalModels, function () use ($modelInstances, $batchSize) {
                foreach ($modelInstances->chunk($batchSize) as $batch) {
                    try {
                        $this->translationService->translateBatchModels($batch);
                    } catch (TranslationException | Throwable $e) {
                        foreach ($batch as $model) {
                            $message = "\nFailed to translate " . $model::class . " (ID: $model->id): " . $e->getMessage();
                            $this->error($message);
                            $this->translationService->logger()->error("$message\n" . $e->getTraceAsString());
                        }
                    }
                }
            });

            $this->newLine();
        }

        $this->info('Translation completed.');
    }

    /**
     * Get the models to be translated.
     *
     * @return array
     */
    protected function getModels(): array
    {
        if ($model = $this->option('model')) {
            return [$model];
        }

        return array_keys($this->config->getModelConfigs());
    }
}
