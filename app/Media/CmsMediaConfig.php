<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

/**
 * Class OrganizationLogoMediaConfig
 * @package App\Media
 */
class CmsMediaConfig extends MediaImageConfig
{
    /**
     * @var array
     */
    protected $name = 'cms_media';

    /**
     * @var string
     */
    protected $type = self::TYPE_MULTIPLE;

    /**
     * FundLogoMediaConfig constructor.
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');
        $this->save_dominant_color = config('media.calc_dominant_color');

        $this->addPreset(new MediaImagePreset('thumbnail', 100, 100, false, 90));
        $this->addPreset((new MediaImagePreset('public', 1000, 1000, true))->setUpscale(false));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}