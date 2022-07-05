<?php

namespace App\Media;

use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaImageConfig;

/**
 * Class OrganizationLogoMediaConfig
 * @package App\Media
 */
class RecordCategoryIconMediaConfig extends MediaImageConfig
{
    /**
     * @var array
     */
    protected $name = 'record_category_icon';

    /**
     * RecordCategoryIconMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail' ,200, 200, false, 90));
        $this->addPreset(new MediaImagePreset('large', 500, 500, false));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}