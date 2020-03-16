<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MediaCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup
                            {minutes_to_expire? : How old in minutes should me media to be considered expired.} 
                            {--force : Do not ask for confirmation before deleting.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean media files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $minutes = null;

        if ($this->hasArgument('minutes_to_expire')) {
            if (!is_numeric($this->argument('minutes_to_expire'))) {
                $this->error("Invalid argument `minutes_to_expire`.\n");
                exit();
            }

            $minutes = intval($this->argument('minutes_to_expire'));
        }

        $this->mediaWithoutMediable();
        $this->expiredMedia($minutes);
        $this->unusedMediaFiles();
    }

    /**
     * @throws \Exception
     */
    public function mediaWithoutMediable() {
        $media = resolve('media');
        $countMedia = count($media->getMediaWithoutMediableList());

        if ($countMedia > 0) {
            echo sprintf("%s medias without mediable where found.\n", $countMedia);

            if ($this->option('force') ||
                $this->confirm("Would you like to delete them?")) {
                echo sprintf(
                    "√ %s media deleted.\n",
                    $media->clearMediasWithoutMediable()
                );
            } else {
                echo sprintf("√ Skipped.\n");
            }
        } else {
            echo sprintf("√ No media without mediable found.\n");
        }
    }

    /**
     * @param int $minutes
     * @throws \Exception
     */
    public function expiredMedia(int $minutes) {
        $media = resolve('media');
        $countMedia = count($media->getExpiredList($minutes ?: 5 * 60));

        if ($countMedia > 0) {
            echo sprintf("%s expired medias found.\n", $countMedia);

            if ($this->option('force') ||
                $this->confirm("Would you like to delete them?")) {
                echo sprintf(
                    "√ %s media deleted.\n",
                    $media->clearExpiredMedias($minutes ?: 5 * 60)
                );
            } else {
                echo sprintf("√ Skipped.\n");
            }
        } else {
            echo sprintf("√ No expired medias found.\n");
        }
    }

    /**
     * @throws \Exception
     */
    public function unusedMediaFiles() {
        $media = resolve('media');
        $countFiles = count($media->getUnusedFilesList());

        if ($countFiles > 0) {
            echo sprintf("%s unused media files found.\n", $countFiles);

            if ($this->option('force') ||
                $this->confirm("Would you like to delete them?")) {
                echo sprintf("√ %s files deleted.\n", $media->clearStorage());
            } else {
                echo sprintf("√ Skipped.\n");
            }
        } else {
            echo sprintf("√ No unused media files found.\n");
        }
    }
}
