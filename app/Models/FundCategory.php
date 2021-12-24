<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundCategory
 *
 * @property int $id
 * @property int $fund_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Tag $tag
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'tag_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tag() {
        return $this->belongsTo(Tag::class);
    }
}
