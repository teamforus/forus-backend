<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $record_type_option_id
 * @property string $locale
 * @property string $name
 * @property-read \App\Models\Tag|null $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOptionTranslation whereRecordTypeOptionId($value)
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
