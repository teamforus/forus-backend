<?php

namespace App\Models;

use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Forus\Identity\Models\Identity;

/**
 * App\Models\PhysicalCard
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $code
 * @property string|null $identity_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Identity|null $identity
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhysicalCard whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCard extends Model
{
    use HasLogs;

    public const EVENT_MIGRATED = 'migrated';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'code', 'identity_address',
    ];

    /**
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }
}
