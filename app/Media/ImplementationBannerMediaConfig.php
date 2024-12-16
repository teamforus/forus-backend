<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class ImplementationBannerMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'implementation_banner';

    protected float $preview_aspect_ratio = 3.33;
    protected bool $save_dominant_color = true;

    /**
     * FundLogoMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');

        $this->addPreset(new MediaImagePreset('thumbnail', 100, 100, false, 90));
        $this->addPreset((new MediaImagePreset('medium', 1000, 300, false, 80, null)));
        $this->addPreset((new MediaImagePreset('large', 2000, 600, false, 80, null)));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}
