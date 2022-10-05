<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Note
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
 * @method static \Illuminate\Database\Eloquent\Builder|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereNotableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereNotableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note wherePinToTop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Note extends Model
{
    use HasFactory;

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
