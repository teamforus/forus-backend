<?php

namespace App\Services\FileService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Media
 *
 * @property mixed $id
 * @property string $identity_address
 * @property string $original_name
 * @property int $fileable_id
 * @property int $fileable_type
 * @property int $size
 * @property string $type
 * @property string $ext
 * @property string $uid
 * @property string $path
 * @property Collection $sizes
 * @property Model $fileable
 * @method static static create($attributes = array())
 * @method static static find($id, $columns = ['*'])
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @package App\Services\FileService\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $url_public
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereExt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereFileableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereFileableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\FileService\Models\File whereUpdatedAt($value)
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
        'fileable_type', 'ext', 'uid', 'path', 'size'
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
        return resolve('file')->urlPublic($this->path);
    }
}
