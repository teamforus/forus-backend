<?php

namespace App\Console\Commands;

use App\Models\Faq;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use Illuminate\Database\Eloquent\Collection;

class SyncAllMarkdownDescriptionMedia extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.markdown-description:sync 
                                {--type= : Model name} 
                                {--dry-run : Show what will change but do not apply}
                                {--detailed : Show detailed media models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all markdown description medias.';

    /**
     * @var bool|null
     */
    private ?bool $dryRun = false;

    /**
     * @var bool|null
     */
    protected ?bool $detailed = false;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $type = strtolower($this->option('type'));
        $dryRun = boolval($this->option('dry-run'));
        $detailed = boolval($this->option('detailed'));

        $this->dryRun = $dryRun;
        $this->detailed = $detailed;

        if (!$this->dryRun) {
            $confirmed = $this->confirm(implode("\n", [
                "You are about to sync markdown medias, which could delete uploaded medias.",
                " It is advised to use '--dry-run' mode to see the changed before applying this command.",
                " Do you want to continue?"
            ]));

            if (!$confirmed) {
                $this->warn('Bye!');
                return 1;
            }
        }

        $this->printHeader("Sync markdown medias" . ($dryRun ? " in dry mode!\n" : "!\n"), 1);

        match($type) {
            'all' => $this->syncAll(),
            'faq' => $this->syncFaq(),
            'fund' => $this->syncFunds(),
            'implementation' => $this->syncImplementations(),
            'implementation_page' => $this->syncImplementationPages(),
            default => $this->warn('Please provide a valid type, ex: --type=fund or --type=all.'),
        };

        return 0;
    }

    /**
     * @param
     * @return void
     * @throws \Throwable
     */
    public function syncAll(): void
    {
        $this->syncFaq();
        $this->syncFunds();
        $this->syncImplementations();
        $this->syncImplementationPages();
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function syncFunds(): Collection
    {
        return $this->syncModels(Fund::get(), 'cms_media', 'Fund');
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function syncFaq(): Collection
    {
        return $this->syncModels(Faq::get(), 'cms_media', 'FundFaq');
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function syncImplementations(): Collection
    {
        return $this->syncModels(Implementation::get(), 'cms_media', 'Implementation');
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function syncImplementationPages(): Collection
    {
        return $this->syncModels(ImplementationPage::get(), 'cms_media', 'ImplementationPage');
    }

    /**
     * @param Collection $models
     * @param string $mediaType
     * @param string $model
     * @return Collection
     * @throws \Throwable
     */
    public function syncModels(Collection $models, string $mediaType, string $model): Collection
    {
        $this->printHeader($this->green("Processing '$model':\n"));

        $models->each(function (Faq|Fund|Implementation|ImplementationPage $model) use ($mediaType) {
            $this->previewModelMedia($model, $mediaType);

            if (!$this->dryRun) {
                $model->syncDescriptionMarkdownMedia($mediaType);
            }

            return $model;
        });

        if ($models->isNotEmpty()) {
            $this->printText();
        }

        $this->printText($this->green(sprintf("%s models processed", $models->count())));
        $this->printSeparator("#");

        return $models;
    }

    /**
     * @param Faq|Fund|Implementation|ImplementationPage $model
     * @param string $mediaType
     * @return void
     */
    protected function previewModelMedia(
        Faq|Fund|Implementation|ImplementationPage $model,
        string $mediaType
    ): void {
        $mediaSynced = $model->medias->where('type', $mediaType);
        $mediaSyncedIds = $mediaSynced->pluck('id');

        $mediaToSync = $model->getDescriptionMarkdownMediaPresetsValidQuery($mediaType)->get();
        $mediaToSyncIds = $mediaToSync->pluck('media_id');

        $mediaToClone = $model->getDescriptionMarkdownMediaPresetsInValidQuery($mediaType)->get();
        $mediaToCloneIds = $mediaToClone->pluck('media_id');

        $this->printText("#$model->id: ");

        if ($this->detailed) {
            $this->printHeader($this->green("Has '" . $mediaSyncedIds . "':\n"));
            $this->printModels($mediaSynced, 'id', 'uid');
        } else {
            $this->printText(sprintf("    - has: %s", $mediaSyncedIds));
        }

        if ($this->detailed) {
            $this->printHeader($this->green("Should '" . $mediaToSyncIds . "':\n"));
            $this->printModels($mediaToSync, 'id', 'uid');
        } else {
            $this->printText(sprintf("    - should: %s", $mediaToSyncIds));
        }

        if ($this->detailed) {
            $this->printHeader($this->green("Should clone '" . $mediaToCloneIds . "':\n"));
            $this->printModels($mediaToClone, 'id', 'uid');
        } else {
            $this->printText(sprintf("    - should clone: %s", $mediaToCloneIds));
        }

        $hasMediaToSync =
            $mediaToCloneIds->isNotEmpty() ||
            $mediaSyncedIds->diff($mediaToSyncIds)->isNotEmpty() ||
            $mediaToSyncIds->diff($mediaSyncedIds)->isNotEmpty();

        if ($hasMediaToSync) {
            $this->printText($this->yellow('    - not in sync!'));
        }
    }
}
