<?php

namespace App\Services\FileService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Media
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
