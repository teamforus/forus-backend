<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProfileRecord.
 *
 * @property int $id
 * @property int $profile_id
 * @property int|null $employee_id
 * @property int $record_type_id
 * @property string $value
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read string|null $value_locale
 * @property-read \App\Models\Profile $profile
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRecord whereValue($value)
 * @mixin \Eloquent
 */
class ProfileRecord extends Model
{
    protected $fillable = [
        'value', 'record_type_id', 'profile_id', 'employee_id',
    ];

    /**
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getValueLocaleAttribute(): ?string
    {
        return match ($this->record_type->type) {
            'date' => $this->value ? format_date_locale($this->value) : $this->value,
            'select',
            'select_number' => $this->record_type
                ?->record_type_options
                ?->firstWhere('value', $this->value)?->name ?: $this->value,
            default => $this->value,
        };
    }
}
