<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Models\Tag
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereName($value)
 * @mixin \Eloquent
 */
class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'key'
    ];

    /**
     * Get all of the funds that are assigned this tag.
     *
     * @return MorphToMany
     */
    public function funds(): MorphToMany
    {
        return $this->morphedByMany(Fund::class, 'taggable');
    }
}