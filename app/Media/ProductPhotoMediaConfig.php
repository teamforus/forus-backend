<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class ProductPhotoMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'product_photo';

    /**
     * ProductPhotoMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail', 200, 200, false, 90));
        $this->addPreset(new MediaImagePreset('small', 400, 300, false));
        $this->addPreset(new MediaImagePreset('large', 1200, 800, false));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}