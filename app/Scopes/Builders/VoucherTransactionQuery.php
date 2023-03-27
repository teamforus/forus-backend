<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
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
            'transaction_in' => self::orderVoucherTransactionIn(),
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

    /**
     * @return \Illuminate\Database\Query\Expression
     */
    private static function orderVoucherTransactionIn(): Expression
    {
        return DB::raw(implode(" ", [
            "IF(",
            "`state` = '" . VoucherTransaction::STATE_PENDING . "' AND `transfer_at` IS NOT NULL,",
            "GREATEST((UNIX_TIMESTAMP(`transfer_at`) - UNIX_TIMESTAMP(current_date)) / 86400, 0), 
            IF(`voucher_transaction_bulk_id` IS NOT NULL, -1, -2)",
            ") as `transaction_in`",
        ]));
    }

    /**
     * @param Builder|QBuilder $query
     * @param string $q
     * @return Builder|QBuilder
     */
    public static function whereQueryFilter(Builder|QBuilder $query, string $q = ''): Builder|QBuilder
    {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('voucher_transactions.uid', '=', $q);
            $query->orWhereHas('voucher.fund', fn (Builder $b) => $b->where('name', 'LIKE', "%$q%"));
            $query->orWhereRelation('product', 'name', 'LIKE', "%$q%");
            $query->orWhereRelation('provider', 'name', 'LIKE', "%$q%");

            if (is_numeric($q)) {
                $query->orWhere('voucher_transactions.id', '=', $q);
                $query->orWhereRelation('product', 'id', "=", $q);
            }
        });
    }
}