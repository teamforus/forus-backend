<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCardRequest
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $address
 * @property string $house
 * @property string|null $house_addition
 * @property string $postcode
 * @property string $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCardRequest whereCreatedAt($value)
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
    /**
     * @var string[]
     */
    protected $fillable = [
        'address', 'house', 'house_addition', 'postcode', 'city',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
