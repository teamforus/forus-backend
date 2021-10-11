<?php

namespace App\Models;

use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCardRequest
 *
 * @property int $id
 * @property int $voucher_id
 * @property int|null $employee_id
 * @property string $address
 * @property string $house
 * @property string|null $house_addition
 * @property string $postcode
 * @property string $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereHouseAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCardRequest extends Model
{
    use HasLogs;

    public const EVENT_CREATED = 'created';

    /**
     * @var string[]
     */
    protected $fillable = [
        'address', 'house', 'house_addition', 'postcode', 'city', 'employee_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
