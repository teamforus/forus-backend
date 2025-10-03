<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class PhysicalCardTypePhotoMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'physical_card_type_photo';

    /**
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail', 200, 200, false, 90));
        $this->addPreset(new MediaImagePreset('small', 400, 400, false));
        $this->addPreset(new MediaImagePreset('large', 800, 800, false));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}
