<?php


namespace App\Searches;

use App\Models\Voucher;
use App\Models\Organization;
use App\Models\PayoutRelation;
use App\Models\ProductReservation;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QBuilder;

class VoucherTransactionsSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder $builder
     */
    public function __construct(array $filters, Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return VoucherTransaction|Builder
     */
    public function query(): ?Builder
    {
        /** @var Builder|VoucherTransaction $builder */
        $builder = parent::query();

        $targets = $this->getFilter('targets', VoucherTransaction::TARGETS_OUTGOING);
        $identity_address = $this->getFilter('identity_address');

        if ($this->hasFilter('q') && $this->getFilter('q')) {
            $builder = VoucherTransactionQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        if ($identity_address) {
            $builder->whereHas('voucher', function (Builder $builder) use ($identity_address) {
                $builder->whereRelation('identity', 'address', $identity_address);
            });
        }

        if ($this->hasFilter('state') && $this->getFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('from') && $this->getFilter('from')) {
            $from = Carbon::createFromFormat('Y-m-d', $this->getFilter('from'));

            $builder->where(
                'created_at',
                '>=',
                $from->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($this->hasFilter('to') && $this->getFilter('to')) {
            $to = Carbon::createFromFormat('Y-m-d', $this->getFilter('to'));

            $builder->where(
                'created_at',
                '<=',
                $to->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($nonCancelableFrom = $this->getFilter('non_cancelable_from')) {
            $from = Carbon::createFromFormat('Y-m-d', $nonCancelableFrom)
                ->startOfDay()
                ->format('Y-m-d H:i:s');

            $builder->where(function (Builder $builder) use ($from) {
                $builder->where(function (Builder $builder) use ($from) {
                    $builder->whereHas('product_reservation');
                    $builder->where('transfer_at', '>=', $from);
                })->orWhere(function (Builder $builder) use ($from) {
                    $builder->whereDoesntHave('product_reservation');
                    $builder->where('created_at', '>=', $from);
                });
            });
        }

        if ($nonCancelableTo = $this->getFilter('non_cancelable_to')) {
            $to = Carbon::createFromFormat('Y-m-d', $nonCancelableTo)
                ->endOfDay()
                ->format('Y-m-d H:i:s');

            $builder->where(function (Builder $builder) use ($to) {
                $builder->where(function (Builder $builder) use ($to) {
                    $builder->whereHas('product_reservation');
                    $builder->where('transfer_at', '<=', $to);
                })->orWhere(function (Builder $builder) use ($to) {
                    $builder->whereDoesntHave('product_reservation');
                    $builder->where('created_at', '<=', $to);
                });
            });
        }

        if ($amount_min = $this->getFilter('amount_min')) {
            $builder->where('amount', '>=', $amount_min);
        }

        if ($amount_max = $this->getFilter('amount_max')) {
            $builder->where('amount', '<=', $amount_max);
        }

        if ($transfer_in_min = $this->getFilter('transfer_in_min')) {
            $builder->where(function (Builder $builder) use ($transfer_in_min) {
                $builder->where('state', VoucherTransaction::STATE_PENDING);
                $builder->where('transfer_at', '>=', now()->addDays($transfer_in_min));
                $builder->whereNull('voucher_transaction_bulk_id');
            });
        }

        if ($transfer_in_max = $this->getFilter('transfer_in_max')) {
            $builder->where(function (Builder $builder) use ($transfer_in_max) {
                $builder->where('state', VoucherTransaction::STATE_PENDING);
                $builder->where('transfer_at', '<=', now()->addDays($transfer_in_max + 1));
                $builder->whereNull('voucher_transaction_bulk_id');
            });
        }

        if ($this->hasFilter('fund_state') && ($fund_state = $this->getFilter('fund_state'))) {
            $builder->whereHas('voucher.fund', fn(Builder $b) => $b->where('state', '=', $fund_state));
        }

        if ($this->hasFilter('bulk_state') && ($bulk_state = $this->getFilter('bulk_state'))) {
            $builder->whereRelation('voucher_transaction_bulk', 'state', $bulk_state);
        }

        $builder->whereIn('target', is_array($targets) ? $targets : []);

        return $builder;
    }

    /**
     * @param Organization $organization
     * @return Builder
     */
    public function searchSponsor(Organization $organization): Builder
    {
        $builder = $this->query();

        $builder = $builder->whereHas('voucher.fund', function (Builder $query) use ($organization) {
            if ($fund_id = $this->getFilter('fund_id')) {
                $query->where('id', $fund_id);
            }

            $query->whereHas('organization', function (Builder $query) use ($organization) {
                $query->where('id', $organization->id);
            });
        });

        if ($voucher_transaction_bulk_id = $this->getFilter('voucher_transaction_bulk_id')) {
            $builder->where(compact('voucher_transaction_bulk_id'));
        }

        if ($voucher_id = $this->getFilter('voucher_id')) {
            $builder->where('voucher_id', $voucher_id);
        }

        if ($reservation_voucher_id = $this->getFilter('reservation_voucher_id')) {
            $builder->whereRelation('product_reservation', 'voucher_id', $reservation_voucher_id);
        }

        if ($this->getFilter('pending_bulking')) {
            VoucherTransactionQuery::whereAvailableForBulking($builder);
        }

        return self::appendSelectPaymentType(self::appendSelectRelation($builder));
    }

    /**
     * @return Builder
     */
    public function searchProvider(): Builder
    {
        $builder = $this->query();

        if ($this->getFilter('fund_id')) {
            $builder->whereRelation('voucher', 'fund_id', $this->getFilter('fund_id'));
        }

        return VoucherTransactionQuery::whereOutgoing($builder);
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    public static function appendSelectPaymentType(Builder|QBuilder $builder): Builder|QBuilder
    {
        $builder = self::appendFundRequestId($builder);
        $builder = self::appendReservationField($builder);

        return $builder->selectRaw(
            'voucher_transactions.*, 
            (
                CASE WHEN `reimbursement_id` IS NOT NULL THEN "reimbursement"
                ELSE (
                    CASE WHEN `product_id` IS NOT NULL THEN (
                        CASE 
                            WHEN `product_reservation_id` IS NOT NULL THEN "product_reservation" 
                            ELSE "product_voucher" END
                    ) ELSE (
                        CASE
                            WHEN `initiator` = "' . VoucherTransaction::INITIATOR_PROVIDER . '" THEN "voucher_scan"
                            WHEN `target` = "' . VoucherTransaction::TARGET_PROVIDER . '" THEN "direct_provider"
                            WHEN `target` = "' . VoucherTransaction::TARGET_IBAN . '" THEN "direct_iban"
                            WHEN `target` = "' . VoucherTransaction::TARGET_TOP_UP . '" THEN "direct_top_up"
                            WHEN `target` = "' . VoucherTransaction::TARGET_PAYOUT . '" THEN (
                                CASE WHEN `fund_request_id` IS NULL THEN (
                                    CASE WHEN `upload_batch_id` IS NOT NULL THEN "payout_bulk" ELSE "payout_single" END
                                ) ELSE "payout_request" END
                            )
                        ELSE NULL END
                    ) END
                ) END
            ) as `payment_type`'
        );
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    private static function appendSelectRelation(Builder|QBuilder $builder): Builder|QBuilder
    {
        $builder->addSelect([
            'relation' => PayoutRelation::query()
                ->whereColumn('voucher_transactions.id', 'voucher_transaction_id')
                ->orderBy('type')
                ->select('value')
                ->take(1),
        ]);

        return VoucherTransaction::fromSub($builder, 'voucher_transactions')->select('*');
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    private static function appendReservationField(Builder|QBuilder $builder): Builder|QBuilder
    {
        $builder->addSelect([
            'product_reservation_id' => ProductReservation::query()
                ->whereColumn('voucher_transactions.id', 'voucher_transaction_id')
                ->select('id')
        ]);

        return VoucherTransaction::fromSub($builder, 'voucher_transactions')->select('*');
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    private static function appendFundRequestId(Builder|QBuilder $builder): Builder|QBuilder
    {
        $builder->addSelect([
            'fund_request_id' => Voucher::query()
                ->whereColumn('id', 'voucher_id')
                ->select('fund_request_id')
        ]);

        return VoucherTransaction::fromSub($builder, 'voucher_transactions')->select('*');
    }
}