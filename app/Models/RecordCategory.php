<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\RecordCategory
 *
 * @property int $id
 * @property string $identity_address
 * @property string $name
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Media|null $icon
 * @property-read \App\Models\Identity $identity
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Record[] $records
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
        'identity_address', 'name', 'order',
    ];

    protected $perPage = 1000;

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Get category icon
     * @return MorphOne
     */
    public function icon(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'record_category_icon'
        ]);
    }
}
