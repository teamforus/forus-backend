<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Note.
 *
 * @property int $id
 * @property string $icon
 * @property string $description
 * @property int $pin_to_top
 * @property string $group
 * @property int|null $employee_id
 * @property string $notable_type
 * @property int $notable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereNotableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereNotableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note wherePinToTop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Note extends Model
{
    protected $fillable = [
        'description', 'employee_id',
    ];

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
