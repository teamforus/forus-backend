<?php

namespace App\Models;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCardRequest
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $address
 * @property string $house
 * @property string $house_number
 * @property string $postcode
 * @property string $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereHouseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereVoucherId($value)
 * @mixin \Eloquent
 * @property string $house_addition
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PhysicalCardRequest whereHouseAddition($value)
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
