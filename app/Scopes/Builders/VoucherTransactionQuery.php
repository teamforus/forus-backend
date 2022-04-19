<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\Organization;
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
        return static::whereReadyForPayment($builder->where('voucher_transactions.amount', '>', 0));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereReadyForPayoutAndAmountIsZero(Builder $builder): Builder
    {
        return self::whereReadyForPayment($builder->where('voucher_transactions.amount', '=', 0));
    }

    /**
     * @param Builder $builder
     * @param string|null $orderBy
     * @param string|null $orderDir
     * @return Builder
     */
    public static function order(
        Builder $builder,
        ?string $orderBy = 'created_at',
        ?string $orderDir = 'desc'
    ): Builder {
        $fields = VoucherTransaction::SORT_BY_FIELDS;

        $builder->addSelect([
            'fund_name' => self::orderFundNameQuery(),
            'provider_name' => self::orderProviderNameQuery(),
        ]);

        return $builder->orderBy(
            $orderBy && in_array($orderBy, $fields) ? $orderBy : 'created_at',
            $orderDir ?: 'desc'
        );
    }

    /**
     * @return Builder
     */
    protected static function orderFundNameQuery(): Builder
    {
        return Fund::whereHas('vouchers', function(Builder $builder) {
            $builder->whereColumn('voucher_transactions.voucher_id', 'vouchers.id');
        })->select('funds.name');
    }

    /**
     * @return Builder
     */
    protected static function orderProviderNameQuery(): Builder
    {
        return Organization::where(function(Builder $builder) {
            $builder->whereColumn('organizations.id', 'voucher_transactions.organization_id');
        })->select('organizations.name');
    }
}
