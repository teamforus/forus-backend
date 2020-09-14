<?php

namespace App\Services\MediaService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Services\MediaService\Models\MediaSize
 *
 * @property int $id
 * @property int $media_id
 * @property string $key
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MediaService\Models\Media $media
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaPreset whereUpdatedAt($value)
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
    public function media(): BelongsTo {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return \App\Services\MediaService\MediaService
     */
    public function service(): mixed {
        return resolve('media');
    }

    /**
     * @return bool
     */
    public function unlink(): bool {
        return $this->service()->deleteFile($this->path);
    }

    /**
     * @return mixed
     */
    public function urlPublic() {
        return $this->service()->urlPublic($this->path);
    }

    /**
     * @return mixed
     */
    public function storagePath() {
        return $this->service()->path($this->path);
    }

    /**
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getContent(): ?string {
        return $this->service()->getContent($this->path);
    }

    /**
     * @return mixed
     */
    public function fileExists() {
        return $this->service()->storageFileExists($this->path);
    }
}
