<?php

namespace App\Services\MediaService\Commands;

use App\Services\MediaService\MediaService;
use Illuminate\Console\Command;
use Throwable;

class MediaRegenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:regenerate 
                            {media_type : Name for the config to be regenerated or `all` to regenerate all media.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate media files largest preset';

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
     * @throws Throwable
     */
    public function handle(): void
    {
        /** @var MediaService $media */
        $media = resolve('media');
        $mediaConfigs = false;
        $mediaType = $this->hasArgument('media_type') ?
            $this->argument('media_type') : false;

        if ($mediaType == 'all') {
            $mediaConfigs = array_values(MediaService::getMediaConfigs());
        } elseif ($mediaConfig = MediaService::getMediaConfig($mediaType)) {
            $mediaConfigs = [$mediaConfig];
        }

        if (!$mediaConfigs) {
            exit(sprintf(
                "Invalid `media_type` value \"%s\" provided.\n",
                $mediaType
            ));
        }

        $totalConfigs = count($mediaConfigs);
        $currentConfig = 0;
        $width = 60;

        $this->header('Media Regeneration Tool', $width);
        $this->info('');

        $callback = function (int $total, int $current) use ($width) {
            $this->textCenter(' - Item: ' . $current . ' / ' . $total, $width);
        };

        foreach ($mediaConfigs as $mediaConfig) {
            $currentConfig++;

            $this->header('Config: ' . $mediaConfig->getName() . " ($currentConfig/$totalConfigs)", $width);
            $media->regenerateMedia($mediaConfig, callback: $callback);
            $this->info(str_repeat('-', $width));
            $this->info('');
        }

        $this->header('Media Regeneration Complete', $width);
    }

    /**
     * @param string $title
     * @param int $width
     * @return void
     */
    public function header(string $title, int $width): void
    {
        $this->info(str_repeat('-', $width));
        $this->textCenter($title, $width);
        $this->info(str_repeat('-', $width));
    }

    /**
     * @param string $text
     * @param int $width
     * @return void
     */
    public function textCenter(string $text, int $width): void
    {
        $leftPadding = floor(($width - strlen($text)) / 2);
        $centeredText = str_repeat(' ', $leftPadding) . $text;

        $this->info($centeredText);
    }
}
