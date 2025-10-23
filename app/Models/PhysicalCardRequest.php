<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCardRequest.
 *
 * @property int $id
 * @property int|null $voucher_id
 * @property int|null $fund_request_id
 * @property int|null $physical_card_type_id
 * @property int|null $employee_id
 * @property string $address
 * @property string $house
 * @property string|null $house_addition
 * @property string $postcode
 * @property string $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\PhysicalCardType|null $physical_card_type
 * @property-read \App\Models\Voucher|null $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereHouseAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest wherePhysicalCardTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCardRequest extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'address', 'house', 'house_addition', 'postcode', 'city', 'employee_id', 'physical_card_type_id',
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

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function physical_card_type(): BelongsTo
    {
        return $this->belongsTo(PhysicalCardType::class);
    }
}
