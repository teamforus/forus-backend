<?php

namespace App\Console\Commands;

use App\Services\MediaService\MediaService;
use Illuminate\Console\Command;

class MediaRegenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:regenerate 
                            {media_type? : Name for the config to be regenerated or `all` to regenerate all media.}';

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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        /** @var MediaService $media */
        $media = resolve('media');
        $mediaConfigs = false;
        $mediaType = $this->hasArgument('media_type') ?
            $this->argument('media_type') : false;

        if ($mediaType == 'all') {
            $mediaConfigs = array_values(MediaService::getMediaConfigs());
        } else if ($mediaConfig = MediaService::getMediaConfig($mediaType)) {
            $mediaConfigs = [$mediaConfig];
        }

        if (!$mediaConfigs) {
            exit(sprintf(
                "Invalid `media_type` value \"%s\" provided.\n",
                $mediaType
            ));
        }

        foreach ($mediaConfigs as $mediaConfig) {
            echo $mediaConfig->getName() . "\n";
            $media->regenerateMedia($mediaConfig);
        }
    }
}
