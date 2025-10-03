<?php

namespace App\Services\TranslationService\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\TranslationService\Models\TranslationCache.
 *
 * @property int $id
 * @property string $translatable_type
 * @property int $translatable_id
 * @property string $key
 * @property string $value
 * @property string $locale
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|Eloquent $translatable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereTranslatableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereTranslatableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationCache whereValue($value)
 * @mixin Eloquent
 */
class TranslationCache extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'translatable_type', 'translatable_id', 'key', 'value', 'locale',
    ];

    /**
     * @return MorphTo
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
