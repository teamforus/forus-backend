<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RecordTypeOption
 *
 * @property int $id
 * @property int $record_type_id
 * @property string $value
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereValue($value)
 * @mixin \Eloquent
 */
class RecordTypeOption extends Model
{
    use HasOnDemandTranslations;

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    protected $fillable = [
        'value', 'name',
    ];
}
