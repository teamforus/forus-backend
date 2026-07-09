<?php

namespace App\Media;

use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;

class FilePdfPreviewPageMediaConfig extends MediaImageConfig
{
    /**
     * @var ?string
     */
    protected ?string $name = 'file_pdf_preview_page';

    /**
     * @var string
     */
    protected string $type = self::TYPE_MULTIPLE;

    protected string $visibility = self::VISIBILITY_PRIVATE;

    /**
     * @throws \App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = false;
        $this->save_dominant_color = false;

        $this->addPreset(MediaImagePreset::createOriginal('original'));
    }
}
