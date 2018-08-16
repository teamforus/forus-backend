<?php

namespace App\Services\MediaService\Models;

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
 * @property Collection $sizes
 * @property Model $mediable
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
}
