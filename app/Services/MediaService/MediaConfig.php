<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException;
use App\Services\MediaService\Models\Media;

/**
 * Class MediaSize
 * @package App\Services\MediaService
 */
abstract class MediaConfig
{
    const TYPE_SINGLE = 'single';
    const TYPE_MULTIPLE = 'multiple';

    const TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
    ];

    protected $presets = [];

    /**
     * @var array
     */
    protected $source_extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp'
    ];

    /**
     * @var array
     */
    protected $source_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
    ];

    /**
     * @var int
     */
    protected $max_source_file_size = null;

    /**
     * @var string Source preset to be used for media regeneration
     */
    protected $regenerate_source = 'original';

    /**
     * @var string Reference name for media
     */
    protected $name = null;

    /**
     * @var string
     */
    protected $type = self::TYPE_SINGLE;

    /**
     * @var bool
     */
    protected $use_queue = false;

    /**
     * @var array
     */
    protected $sync_presets = [
        'thumbnail'
    ];

    /**
     * @var bool
     */
    protected $save_dominant_color = false;

    /**
     * @return string|null
     */
    public function getName() {
        return $this->name ?? null;
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type ?? null;
    }

    /**
     * @param MediaPreset $mediaPreset
     * @param bool $overwriteIfExists
     * @return MediaPreset
     * @throws MediaPresetAlreadyExistsException
     */
    protected function addPreset(
        MediaPreset $mediaPreset,
        bool $overwriteIfExists = false
    ) {
        if (!$overwriteIfExists && isset($this->presets[$mediaPreset->name])) {
            throw new MediaPresetAlreadyExistsException(sprintf(
                "Media config '%s' already has '%s' preset.",
                $this->getName(),
                $mediaPreset->name
            ));
        }

        return $this->presets[$mediaPreset->name] = $mediaPreset;
    }

    /**
     * @return MediaPreset[]
     */
    public function getPresets() {
        return $this->presets ?? [];
    }

    /**
     * @return string
     */
    public function getRegenerationPresetName() {
        return $this->regenerate_source;
    }

    /**
     * @return string[]
     */
    public function getSourceExtensions() {
        return $this->source_extensions ?? [];
    }

    /**
     * @return string[]
     */
    public function getSourceMimeTypes() {
        return $this->source_mime_types ?? [];
    }

    /**
     * @param int $default
     * @return int
     */
    public function getMaxSourceFileSize(int $default) {
        return $this->max_source_file_size ?: $default;
    }

    /**
     * @return bool
     */
    public function useQueue() {
        return $this->use_queue;
    }

    /**
     * @return array|bool
     */
    public function getSyncPresets() {
        return $this->regenerate_source ? array_merge(
            $this->sync_presets, (array) $this->regenerate_source
        ): $this->sync_presets;
    }

    /**
     * @param Media $media
     * @param bool $fromQueue
     * @return mixed
     */
    abstract public function onMediaPresetsUpdated(Media $media, bool $fromQueue = false);
}