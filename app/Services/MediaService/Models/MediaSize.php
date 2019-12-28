<?php

namespace App\Services\MediaService\Models;

use Illuminate\Database\Eloquent\Model;

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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\MediaSize whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MediaSize extends Model
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

    public function unlink() {
        return resolve('media')->deleteFile($this->path);
    }

    public function urlPublic() {
        return resolve('media')->urlPublic($this->path);
    }

    public function storagePath() {
        return resolve('media')->path($this->path);
    }

    public function fileExists() {
        resolve('media')->storageFileExists($this->path);
    }
}
