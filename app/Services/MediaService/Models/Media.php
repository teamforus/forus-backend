<?php

namespace App\Services\MediaService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\MediaService\Models\Media
 *
 * @property int $id
 * @property string|null $uid
 * @property string|null $original_name
 * @property string $type
 * @property string $ext
 * @property string $dominant_color
 * @property int $order
 * @property string $identity_address
 * @property int|null $mediable_id
 * @property string|null $mediable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool|null $is_dark
 * @property-read Model|\Eloquent $mediable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\MediaPreset[] $presets
 * @property-read int|null $presets_count
 * @property-read \App\Services\MediaService\Models\MediaPreset|null $size_original
 * @method static Builder|Media newModelQuery()
 * @method static Builder|Media newQuery()
 * @method static Builder|Media query()
 * @method static Builder|Media whereCreatedAt($value)
 * @method static Builder|Media whereDominantColor($value)
 * @method static Builder|Media whereExt($value)
 * @method static Builder|Media whereId($value)
 * @method static Builder|Media whereIdentityAddress($value)
 * @method static Builder|Media whereMediableId($value)
 * @method static Builder|Media whereMediableType($value)
 * @method static Builder|Media whereOrder($value)
 * @method static Builder|Media whereOriginalName($value)
 * @method static Builder|Media whereType($value)
 * @method static Builder|Media whereUid($value)
 * @method static Builder|Media whereUpdatedAt($value)
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
        'type', 'ext', 'uid', 'dominant_color', 'order',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function presets(): HasMany
    {
        return $this->hasMany(MediaPreset::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function size_original(): HasOne
    {
        return $this->hasOne(MediaPreset::class)->where([
            'key' => 'original'
        ]);
    }

    /**
     * @return MorphTo
     */
    public function mediable() : MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param string $key
     * @return MediaPreset|null
     */
    public function findPreset(string $key): ?MediaPreset
    {
        return $this->presets->where('key', $key)->first();
    }

    /**
     * @param $uid
     * @return self|Builder|Model|object|null
     */
    public static function findByUid($uid)
    {
        return self::where(compact('uid'))->first();
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function urlPublic(string $key): ?string
    {
        if ($size = $this->findPreset($key)) {
            return $size->urlPublic();
        }

        return null;
    }

    /**
     * @return bool|null
     * @noinspection PhpUnused
     */
    public function getIsDarkAttribute(): ?bool
    {
        return $this->dominant_color ? is_color_dark($this->dominant_color) : null;
    }
}
