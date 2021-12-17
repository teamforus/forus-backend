<?php


namespace App\Scopes\Builders;

use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VoucherQuery
 * @package App\Scopes\Builders
 */
class VoucherTransactionQuery
{
    /**
     * @param Builder $builder
     * @return Builder
     */
    protected static function whereReadyForPayment(Builder $builder): Builder
    {
        $builder->where('voucher_transactions.state', VoucherTransaction::STATE_PENDING);
        $builder->whereNull('voucher_transaction_bulk_id');

        // To exclude transactions marked for review and those which failed before the update
        $builder->where('attempts', '<=', 3);

        $builder->whereDoesntHave('provider', function(Builder $builder) {
            $builder->whereIn('iban', config('bunq.skip_iban_numbers'));
        });

        return $builder->where(function(Builder $query) {
            $query->whereNull('transfer_at');
            $query->orWhereDate('transfer_at', '<', now());
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereAvailableForBulking(Builder $builder): Builder
    {
        return static::whereReadyForPayment($builder->where('amount', '>', 0));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereReadyForPayoutAndAmountIsZero(Builder $builder): Builder
    {
        return self::whereReadyForPayment($builder->where('amount', '=', 0));
    }
}
