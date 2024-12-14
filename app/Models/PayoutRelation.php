<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int|null $voucher_transaction_id
 * @property string|null $type
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VoucherTransaction|null $voucher_transaction
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayoutRelation whereVoucherTransactionId($value)
 * @mixin \Eloquent
 */
class PayoutRelation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'voucher_transaction_id', 'type', 'value',
    ];

    /**
     * @return BelongsTo
     */
    public function voucher_transaction(): BelongsTo
    {
        return $this->belongsTo(VoucherTransaction::class);
    }
}
