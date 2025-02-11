<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $tag_id
 * @property string $locale
 * @property string $name
 * @property-read \App\Models\Tag $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTranslation whereTagId($value)
 * @mixin \Eloquent
 */
class RecordTypeOptionTranslation extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Get the tag that owns this translation.
     *
     * @return BelongsTo
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
