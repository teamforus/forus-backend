<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

/**
 * Class OrganizationLogoMediaConfig
 * @package App\Media
 */
class OrganizationLogoMediaConfig extends MediaImageConfig
{
    /**
     * @var array
     */
    protected $name = 'organization_logo';

    /**
     * OrganizationLogoMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail', 200, 200, false, 90));
        $this->addPreset(new MediaImagePreset('large', 500, 500, false));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}