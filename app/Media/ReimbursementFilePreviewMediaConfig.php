<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class ReimbursementFilePreviewMediaConfig extends MediaImageConfig
{
    /**
     * @var array
     */
    protected $name = 'reimbursement_file_preview';

    protected $preview_aspect_ratio = 1.33;
    protected $save_dominant_color = true;

    /**
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('media.use_queue');

        $this->addPreset(new MediaImagePreset('thumbnail', 320, 240, false, 90));
        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}
