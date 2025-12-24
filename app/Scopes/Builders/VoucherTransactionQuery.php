<?php

namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\IdentityEmail;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class VoucherTransactionQuery
{
    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereAvailableForBulking(
        Builder|Relation|VoucherTransaction $builder,
    ): Builder|Relation|VoucherTransaction {
        return static::whereReadyForPayment($builder->where('voucher_transactions.amount', '>', 0));
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereReadyForPayoutAndAmountIsZero(
        Builder|Relation|VoucherTransaction $builder
    ): Builder|Relation|VoucherTransaction {
        return self::whereReadyForPayment($builder->where('voucher_transactions.amount', '=', 0));
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @param string|null $orderBy
     * @param string|null $orderDir
     * @return Builder|Relation|VoucherTransaction
     */
    public static function order(
        Builder|Relation|VoucherTransaction $builder,
        ?string $orderBy = 'created_at',
        ?string $orderDir = 'desc'
    ): Builder|Relation|VoucherTransaction {
        $fields = VoucherTransaction::SORT_BY_FIELDS;

        $builder->addSelect([
            'fund_name' => self::orderFundNameQuery(),
            'product_name' => self::orderProductNameQuery(),
            'provider_name' => self::orderProviderNameQuery(),
            'transfer_in' => self::orderVoucherTransferIn(),
            'bulk_state' => self::orderBulkState(),
            'bulk_id' => self::orderBulkId(),
            'employee_email' => self::orderEmployeeEmail(),
        ]);

        if ($orderBy == 'date_non_cancelable') {
            $orderBy = 'transfer_at';
        }

        return $builder->orderBy(
            $orderBy && in_array($orderBy, $fields) ? $orderBy : 'created_at',
            $orderDir ?: 'desc'
        );
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereOutgoing(
        Builder|Relation|VoucherTransaction $builder,
    ): Builder|Relation|VoucherTransaction {
        return $builder->whereIn('target', VoucherTransaction::TARGETS_OUTGOING);
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereIncoming(
        Builder|Relation|VoucherTransaction $builder,
    ): Builder|Relation|VoucherTransaction {
        return $builder->whereIn('target', VoucherTransaction::TARGETS_INCOMING);
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereRequesterPayouts(
        Builder|Relation|VoucherTransaction $builder,
    ): Builder|Relation|VoucherTransaction {
        return $builder
            ->where('target', VoucherTransaction::TARGET_PAYOUT)
            ->where('initiator', VoucherTransaction::INITIATOR_REQUESTER);
    }

    /**
     * @param Voucher $voucher
     * @param bool $lock
     * @return int
     */
    public static function countRequesterPayouts(Voucher $voucher, bool $lock = false): int
    {
        $query = self::whereRequesterPayouts(
            VoucherTransaction::query()->where('voucher_id', $voucher->id),
        );

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->count();
    }

    /**
     * @param Builder|Relation|VoucherTransaction $query
     * @param string $q
     * @param string|null $q_type
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereQueryFilter(
        Builder|Relation|VoucherTransaction $query,
        string $q = '',
        ?string $q_type = null,
    ): Builder|Relation|VoucherTransaction {
        return $query->where(static function (Builder $query) use ($q, $q_type) {
            $query->where('voucher_transactions.uid', '=', $q);
            $query->orWhereHas('voucher.fund', fn (Builder $b) => $b->where('name', 'LIKE', "%$q%"));
            $query->orWhereRelation('product', 'name', 'LIKE', "%$q%");
            $query->orWhereRelation('provider', 'name', 'LIKE', "%$q%");

            $query->orWhereHas('employee.office', function (Builder $builder) use ($q) {
                $builder->where('branch_name', 'LIKE', "%$q%");
                $builder->orWhere('branch_number', 'LIKE', "%$q%");
                $builder->orWhere('branch_id', 'LIKE', "%$q%");
            });

            if ($q_type === 'provider') {
                $query->orWhereRelation('notes_provider', 'message', 'LIKE', "%$q%");
            }

            if (is_numeric($q)) {
                $query->orWhere('voucher_transactions.id', '=', $q);
                $query->orWhereRelation('product', 'id', '=', $q);
            }
        });
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @param bool $hasPayouts
     * @return Builder|Relation|VoucherTransaction
     */
    public static function whereIsPaidOutQuery(
        Builder|Relation|VoucherTransaction $builder,
        bool $hasPayouts = true,
    ): Builder|Relation|VoucherTransaction {
        return $builder->where(function (Builder|VoucherTransaction $builder) use ($hasPayouts) {
            $builder->whereRelation('voucher_transaction_bulk', function (
                Builder|VoucherTransactionBulk $builder
            ) {
                $builder->where('state', VoucherTransactionBulk::STATE_ACCEPTED);
            });
        });
    }

    /**
     * @param Builder|Relation|VoucherTransaction $builder
     * @return Builder|Relation|VoucherTransaction
     */
    protected static function whereReadyForPayment(
        Builder|Relation|VoucherTransaction $builder
    ): Builder|Relation|VoucherTransaction {
        VoucherTransactionQuery::whereOutgoing($builder);

        $builder->where('voucher_transactions.state', VoucherTransaction::STATE_PENDING);
        $builder->whereNull('voucher_transaction_bulk_id');

        // To exclude transactions marked for review and those which failed before the update
        $builder->where('attempts', '<=', 3);

        $builder->whereDoesntHave('provider', function (Builder $builder) {
            $builder->whereIn('iban', config('bunq.skip_iban_numbers'));
        });

        return $builder->where(function (Builder $query) {
            $query->whereNull('transfer_at');
            $query->orWhereDate('transfer_at', '<', now()->format('Y-m-d H:i:s'));
        });
    }

    /**
     * @return Builder|Relation|Fund
     */
    protected static function orderFundNameQuery(): Builder|Relation|Fund
    {
        return Fund::whereHas('vouchers', function (Builder $builder) {
            $builder->whereColumn('voucher_transactions.voucher_id', 'vouchers.id');
        })->select('name');
    }

    /**
     * @return Builder|Relation|Organization
     */
    protected static function orderProviderNameQuery(): Builder|Relation|Organization
    {
        return Organization::whereColumn('id', 'organization_id')->select('name');
    }

    /**
     * @return Builder|Relation|Product
     */
    protected static function orderProductNameQuery(): Builder|Relation|Product
    {
        return Product::whereColumn('id', 'product_id')->select('name');
    }

    /**
     * @return Builder|Relation|VoucherTransactionBulk
     */
    protected static function orderBulkState(): Builder|Relation|VoucherTransactionBulk
    {
        return VoucherTransactionBulk::query()
            ->whereColumn('id', 'voucher_transaction_bulk_id')
            ->select('state');
    }

    /**
     * @return Builder|Relation|VoucherTransactionBulk
     */
    protected static function orderBulkId(): Builder|Relation|VoucherTransactionBulk
    {
        return VoucherTransactionBulk::query()
            ->whereColumn('id', 'voucher_transaction_bulk_id')
            ->select('id');
    }

    /**
     * @return Builder|Relation|IdentityEmail
     */
    protected static function orderEmployeeEmail(): Builder|Relation|IdentityEmail
    {
        return IdentityEmail::query()
            ->where('primary', true)
            ->whereHas('identity.employees', fn (Builder $builder) => $builder->whereColumn([
                'employees.id' => 'employee_id',
            ]))
            ->select('email');
    }

    /**
     * @return \Illuminate\Database\Query\Expression
     */
    private static function orderVoucherTransferIn(): Expression
    {
        return DB::raw(implode(' ', [
            'IF(',
            "`state` = '" . VoucherTransaction::STATE_PENDING . "' AND `transfer_at` IS NOT NULL,",
            'GREATEST((UNIX_TIMESTAMP(`transfer_at`) - UNIX_TIMESTAMP(current_date)) / 86400, 0), 
            IF(`voucher_transaction_bulk_id` IS NOT NULL, -1, -2)',
            ') as `transfer_in`',
        ]));
    }
}
