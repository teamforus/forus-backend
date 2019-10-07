<?php

namespace App\Services\MediaService\Models;

use App\Services\FileService\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Media
 * @property mixed $id
 * @property string $identity_address
 * @property string $original_name
 * @property int $mediable_id
 * @property int $mediable_type
 * @property string $type
 * @property string $ext
 * @property string $uid
 * @property MediaSize[]|Collection $sizes
 * @property MediaSize $size_original
 * @property Model $mediable
 * @method static static create($attributes = array())
 * @method static static find($id, $columns = ['*'])
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @package App\Services\MediaService\Models
 */
class Media extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sizes() {
        return $this->hasMany(MediaSize::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function size_original() {
        return $this->hasOne(MediaSize::class)->where([
            'key' => 'original'
        ]);
    }

    /**
     * @return MorphTo
     */
    public function mediable() {
        return $this->morphTo();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'original_name', 'mediable_id', 'mediable_type',
        'type', 'ext', 'uid'
    ];

    /**
     * @param string $key
     * @return MediaSize|null
     */
    public function findSize(string $key) {
        return $this->sizes->where('key', $key)->first();
    }

    /**
     * @param $uid
     * @return self|Builder|Model|object|null
     */
    public static function findByUid($uid) {
        return self::where(compact('uid'))->first();
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function urlPublic(string $key) {
        if ($size = $this->findSize($key)) {
            return $size->urlPublic();
        }

        return null;
    }
}
