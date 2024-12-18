<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Exceptions\MediaPresetAlreadyExistsException;
use App\Services\MediaService\Models\Media;

abstract class MediaConfig
{
    const string TYPE_SINGLE = 'single';
    const string TYPE_MULTIPLE = 'multiple';

    const array TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
    ];

    protected array $presets = [];

    /**
     * @var array
     */
    protected array $source_extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp'
    ];

    /**
     * @var array
     */
    protected array $source_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
    ];

    /**
     * @var ?int
     */
    protected ?int $max_source_file_size = null;

    /**
     * @var string Source preset to be used for media regeneration
     */
    protected string $regenerate_source = 'original';

    /**
     * @var ?string Reference name for media
     */
    protected ?string $name = null;

    /**
     * @var string
     */
    protected string $type = self::TYPE_SINGLE;

    /**
     * @var bool
     */
    protected bool $use_queue = false;

    /**
     * @var array
     */
    protected array $sync_presets = [
        'thumbnail'
    ];

    /**
     * @var bool
     */
    protected bool $save_dominant_color = true;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
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
    ): MediaPreset {
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
    public function getPresets(): array
    {
        return $this->presets ?? [];
    }

    /**
     * @return string
     */
    public function getRegenerationPresetName(): string
    {
        return $this->regenerate_source;
    }

    /**
     * @return string[]
     */
    public function getSourceExtensions(): array
    {
        return $this->source_extensions ?? [];
    }

    /**
     * @return string[]
     */
    public function getSourceMimeTypes(): array
    {
        return $this->source_mime_types ?? [];
    }

    /**
     * @param int $default
     * @return int
     */
    public function getMaxSourceFileSize(int $default): int
    {
        return $this->max_source_file_size ?: $default;
    }

    /**
     * @return bool
     */
    public function useQueue(): bool
    {
        return $this->use_queue;
    }

    /**
     * @return array
     */
    public function getSyncPresets(): array
    {
        return $this->regenerate_source ? array_merge(
            $this->sync_presets, (array) $this->regenerate_source
        ): $this->sync_presets;
    }

    /**
     * @param Media $media
     * @param bool $fromQueue
     * @return void
     */
    abstract public function onMediaPresetsUpdated(Media $media, bool $fromQueue = false): void;
}