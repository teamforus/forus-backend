<?php


namespace App\Searches;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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

        if ($this->hasFilter('q') && $this->getFilter('q')) {
            $builder = VoucherTransactionQuery::whereQueryFilter($builder, $this->getFilter('q'));
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
            $builder->whereHas('voucher.fund', fn (Builder $b) => $b->where('state', '=', $fund_state));
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

        return $builder;
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
}