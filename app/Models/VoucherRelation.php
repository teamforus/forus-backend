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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\VoucherRelation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherRelation whereVoucherId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\VoucherRelation withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\VoucherRelation withoutTrashed()
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
    public function voucher(): BelongsTo {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return bool
     */
    public function assignIfExists(): bool
    {
        $identity_address = record_repo()->identityAddressByBsn($this->bsn);

        if (!$identity_address || $this->voucher->identity_address) {
            return false;
        }

        $this->voucher->assignToIdentity($identity_address);
        return true;
    }
}
