<?php
namespace App\Services\MediaService\Jobs;

use App\Services\MediaService\MediaConfig;
use App\Services\MediaService\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mediaConfig;
    protected $media;

    /**
     * Create a new job instance.
     *
     * RegenerateMediaJob constructor.
     * @param MediaConfig $mediaConfig
     * @param Media|null $media
     */
    public function __construct(
        MediaConfig $mediaConfig,
        Media $media = null
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): void
    {
        media()->regenerateMedia(
            $this->mediaConfig,
            $this->media,
            true
        );
    }
}
