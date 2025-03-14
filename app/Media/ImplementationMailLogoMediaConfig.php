<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class ImplementationMailLogoMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'email_logo';

    /**
     * @var float
     */
    protected float $preview_aspect_ratio = 1;

    /**
     * OrganizationLogoMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail', 150, 150, true, 90));
        $this->addPreset(new MediaImagePreset('large', 300, 300, true, 90));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}
