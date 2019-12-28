<?php

namespace App\Services\MediaService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\MediaService\Models\Media
 *
 * @property int $id
 * @property string|null $uid
 * @property string|null $original_name
 * @property string $type
 * @property string $ext
 * @property string $identity_address
 * @property int|null $mediable_id
 * @property string|null $mediable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MediaService\Models\Media|null $mediable
 * @property-read \App\Services\MediaService\Models\MediaSize $size_original
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\MediaSize[] $sizes
 * @property-read int|null $sizes_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereExt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereMediableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereMediableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\MediaService\Models\Media whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Media extends Model
{
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
