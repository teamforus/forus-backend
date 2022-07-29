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

    /**
     * @var MediaConfig
     */
    protected MediaConfig $mediaConfig;

    /**
     * @var Media|null
     */
    protected ?Media $media;

    /**
     * @var Media[]|null
     */
    protected ?array $keepPresets;

    /**
     * Create a new job instance.
     *
     * RegenerateMediaJob constructor.
     * @param MediaConfig $mediaConfig
     * @param Media|null $media
     * @param Media[]|null $keepPresets
     */
    public function __construct(
        MediaConfig $mediaConfig,
        Media $media = null,
        ?array $keepPresets = []
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->media = $media;
        $this->keepPresets = $keepPresets;
    }

    /**
     * Execute the job.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): void
    {
        resolve('media')->regenerateMedia(
            $this->mediaConfig,
            $this->media,
            true,
            $this->keepPresets
        );
    }
}
