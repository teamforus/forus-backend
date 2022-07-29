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
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation newQuery()
 * @method static \Illuminate\Database\Query\Builder|VoucherRelation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRelation whereVoucherId($value)
 * @method static \Illuminate\Database\Query\Builder|VoucherRelation withTrashed()
 * @method static \Illuminate\Database\Query\Builder|VoucherRelation withoutTrashed()
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
    public function assignIfExists(): bool
    {
        $identity = Identity::findByBsn($this->bsn);

        if (!$identity || $this->voucher->identity_address) {
            return false;
        }

        return (bool) $this->voucher->assignToIdentity($identity);
    }
}
