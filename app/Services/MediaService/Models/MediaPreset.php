<?php

namespace App\Services\MediaService\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\MediaService\Models\MediaPreset
 *
 * @property int $id
 * @property int $media_id
 * @property string $key
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MediaService\Models\Media $media
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset query()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaPreset whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MediaPreset extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'media_id', 'key', 'path'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function media() {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return \App\Services\MediaService\MediaService
     */
    public function service() {
        return resolve('media');
    }

    /**
     * @return bool
     */
    public function unlink(): bool
    {
        return self::service()->deleteFile($this->path);
    }

    /**
     * @return string
     */
    public function urlPublic(): string
    {
        return self::service()->urlPublic($this->path);
    }

    /**
     * @return string
     */
    public function storagePath(): string
    {
        return self::service()->path($this->path);
    }

    /**
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getContent() {
        return self::service()->getContent($this->path);
    }

    /**
     * @return mixed
     */
    public function fileExists() {
        return self::service()->storageFileExists($this->path);
    }
}
