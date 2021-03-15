<?php

namespace App\Services\Forus\Record\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Services\MediaService\Models\Media;

/**
 * App\Services\Forus\Record\Models\RecordCategory
 *
 * @property int $id
 * @property string $identity_address
 * @property string $name
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Media|null $icon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Record\Models\Record[] $records
 * @property-read int|null $records_count
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecordCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'order'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records()
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Get category icon
     * @return MorphOne
     */
    public function icon()
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'record_category_icon'
        ]);
    }
}
