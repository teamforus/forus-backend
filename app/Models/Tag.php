<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Models\Tag
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string $scope
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereScope($value)
 * @mixin \Eloquent
 */
class Tag extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'key', 
    ];

    /**
     * @var int
     */
    protected $perPage = 100;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get all funds with the tag
     *
     * @return MorphToMany
     */
    public function funds(): MorphToMany
    {
        return $this->morphedByMany(Fund::class, 'taggable');
    }
}