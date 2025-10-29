<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\PreCheckRecord.
 *
 * @property int $id
 * @property string $record_type_key
 * @property int|null $pre_check_id
 * @property int $implementation_id
 * @property int|null $order
 * @property string $title
 * @property string $title_short
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @property-read \App\Models\PreCheck|null $pre_check
 * @property-read \App\Models\RecordType|null $record_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PreCheckRecordSetting[] $settings
 * @property-read int|null $settings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord wherePreCheckId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereTitleShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PreCheckRecord extends Model
{
    use HasOnDemandTranslations;

    /**
     * @var array
     */
    protected $fillable = [
        'record_type_key', 'pre_check_id', 'order', 'title', 'title_short', 'description',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function pre_check(): BelongsTo
    {
        return $this->belongsTo(PreCheck::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function settings(): HasMany
    {
        return $this->hasMany(PreCheckRecordSetting::class);
    }
}
