<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCard
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCard extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'code',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo {
        return $this->belongsTo(Voucher::class);
    }
}
