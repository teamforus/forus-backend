<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class ReimbursementFilePreviewMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'reimbursement_file_preview';

    /**
     * @var float
     */
    protected float $preview_aspect_ratio = 1.33;

    /**
     * @var bool
     */
    protected bool $save_dominant_color = true;

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
