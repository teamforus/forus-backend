<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\VoucherRelation
 *
 * @property int $id
 * @property int $voucher_id
 * @property string|null $bsn
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereVoucherId($value)
 * @mixin \Eloquent
 */
class VoucherRelation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'voucher_id', 'bsn'
    ];

    /**
     * @return BelongsTo
     */
    public function voucher(): BelongsTo {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return bool
     */
    public function assignIfExists(): bool
    {
        if ($this->voucher->identity_address ||
            !($identity_address = record_repo()->identityAddressByBsn($this->bsn))) {
            return false;
        }

        $this->voucher->assignToIdentity($identity_address);
        return true;
    }
}
