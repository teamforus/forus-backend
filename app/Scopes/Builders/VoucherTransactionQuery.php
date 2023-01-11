<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QBuilder;

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
        VoucherTransactionQuery::whereOutgoing($builder);

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
            'product_name' => self::orderProductNameQuery(),
            'provider_name' => self::orderProviderNameQuery(),
        ]);

        return $builder->orderBy(
            $orderBy && in_array($orderBy, $fields) ? $orderBy : 'created_at',
            $orderDir ?: 'desc'
        );
    }

    /**
     * @return Builder|QBuilder
     */
    protected static function orderFundNameQuery(): Builder|QBuilder
    {
        return Fund::whereHas('vouchers', function(Builder $builder) {
            $builder->whereColumn('voucher_transactions.voucher_id', 'vouchers.id');
        })->select('name');
    }

    /**
     * @return Builder|QBuilder
     */
    protected static function orderProviderNameQuery(): Builder|QBuilder
    {
        return Organization::whereColumn('id', 'organization_id')->select('name');
    }

    /**
     * @return Builder|QBuilder
     */
    protected static function orderProductNameQuery(): Builder|QBuilder
    {
        return Product::whereColumn('id', 'product_id')->select('name');
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    public static function whereOutgoing(Builder|QBuilder $builder): Builder|QBuilder
    {
        return $builder->whereIn('target', VoucherTransaction::TARGETS_OUTGOING);
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    public static function whereIncoming(Builder|QBuilder $builder): Builder|QBuilder
    {
        return $builder->whereIn('target', VoucherTransaction::TARGETS_INCOMING);
    }
}
