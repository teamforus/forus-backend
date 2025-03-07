<?php

namespace App\Models;

use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PhysicalCard.
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $code
 * @property string|null $identity_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Identity|null $identity
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCard whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCard extends BaseModel
{
    use HasLogs;

    public const string EVENT_MIGRATED = 'migrated';

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
