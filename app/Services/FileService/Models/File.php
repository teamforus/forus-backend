<?php

namespace App\Services\FileService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\FileService\Models\File
 *
 * @property int $id
 * @property string|null $uid
 * @property string|null $original_name
 * @property string $type
 * @property string $ext
 * @property string $path
 * @property string $size
 * @property string $identity_address
 * @property int|null $fileable_id
 * @property string|null $fileable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $fileable
 * @property-read mixed $url_public
 * @method static Builder|File newModelQuery()
 * @method static Builder|File newQuery()
 * @method static Builder|File query()
 * @method static Builder|File whereCreatedAt($value)
 * @method static Builder|File whereExt($value)
 * @method static Builder|File whereFileableId($value)
 * @method static Builder|File whereFileableType($value)
 * @method static Builder|File whereId($value)
 * @method static Builder|File whereIdentityAddress($value)
 * @method static Builder|File whereOriginalName($value)
 * @method static Builder|File wherePath($value)
 * @method static Builder|File whereSize($value)
 * @method static Builder|File whereType($value)
 * @method static Builder|File whereUid($value)
 * @method static Builder|File whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class File extends Model
{
    /**
     * @return MorphTo
     */
    public function fileable() {
        return $this->morphTo();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'original_name', 'fileable_id',
        'fileable_type', 'ext', 'uid', 'path', 'size', 'type',
    ];

    public function getUrlPublicAttribute() {
        return $this->urlPublic();
    }

    /**
     * @param $uid
     * @return self|Builder|Model|object|null
     */
    public static function findByUid($uid) {
        return self::where(compact('uid'))->first();
    }

    /**
     * @return mixed
     */
    public function urlPublic() {
        return resolve('file')->urlPublic(ltrim($this->path, '/'));
    }

    /**
     * @return mixed
     */
    public function download() {
        return resolve('file')->download(ltrim($this->path, '/'));
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function unlink() {
        return resolve('file')->unlink($this);
    }
}
