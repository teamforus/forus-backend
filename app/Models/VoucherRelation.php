<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\VoucherRelation
 *
 * @property int $id
 * @property int $voucher_id
 * @property string|null $bsn
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRelation withoutTrashed()
 * @mixin \Eloquent
 */
class VoucherRelation extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'voucher_id', 'bsn'
    ];

    /**
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return bool
     */
    public function assignByBsnIfExists(): bool
    {
        $identity = Identity::findByBsn($this->bsn);

        if (!$identity || $this->voucher->identity_id) {
            return false;
        }

        return (bool) $this->voucher->assignToIdentity($identity);
    }
}
