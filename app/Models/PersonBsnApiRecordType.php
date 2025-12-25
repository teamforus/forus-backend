<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 *
 *
 * @property int $id
 * @property string $person_bsn_api_field
 * @property string $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType wherePersonBsnApiField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PersonBsnApiRecordType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PersonBsnApiRecordType extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'person_bsn_api_field', 'record_type_key',
    ];

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }

    /**
     * @param string|null $value
     * @param string $control_type
     * @return float|int|string|null
     */
    public function parsePersonValue(?string $value, string $control_type): float|int|string|null
    {
        if (!$value) {
            return null;
        }

        return match ($control_type) {
            'number',
            'currency' => (float) $value,
            'step' => (int) $value,
            'date' => $this->parseDate($value),
            default => $value,
        };
    }

    /**
     * @param string $date
     * @return string|null
     */
    private function parseDate(string $date): ?string
    {
        try {
            return Carbon::parse($date)->format('d-m-Y');
        } catch (Throwable) {
            return null;
        }
    }
}
