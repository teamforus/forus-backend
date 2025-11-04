<?php

namespace App\Searches;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use App\Scopes\Builders\VoucherSubQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class VouchersSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Voucher $builder
     */
    public function __construct(array $filters, Builder|Relation|Voucher $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Voucher
     */
    public function query(): Builder|Relation|Voucher
    {
        /** @var Relation|Builder|Voucher $builder */
        $builder = parent::query();
        $builder->where('voucher_type', Voucher::VOUCHER_TYPE_VOUCHER);

        if ($this->getFilter('type') === Voucher::TYPE_BUDGET) {
            $builder->whereNull('product_id');
        }

        if ($this->getFilter('type') === Voucher::TYPE_PRODUCT) {
            $builder->whereNotNull('product_id');
        }

        if ($this->getFilter('allow_reimbursements')) {
            VoucherQuery::whereAllowReimbursements($builder);
        }

        $this->filterByImplementation($builder);

        if ($this->getFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('archived')) {
            $this->getFilter('archived') ?
                VoucherQuery::whereExpiredOrNotActive($builder) :
                VoucherQuery::whereNotExpiredAndActive($builder);
        }

        return $this->order($builder);
    }

    /**
     * @param Organization $organization
     * @param Fund|null $fund
     * @return Relation|Builder|Voucher
     */
    public function searchSponsor(Organization $organization, ?Fund $fund = null): Relation|Builder|Voucher
    {
        /** @var Relation|Builder|Voucher $builder */
        $builder = parent::query();

        $builder = VoucherQuery::whereVisibleToSponsor($builder);
        $builder->where('voucher_type', Voucher::VOUCHER_TYPE_VOUCHER);

        $builder->whereHas('fund', static function (Builder $query) use ($organization, $fund) {
            $query->where('organization_id', $organization->id);
            $fund && $query->where('id', $fund->id);
        });

        if ($this->getFilter('expired') !== null) {
            $builder = $this->getFilter('expired')
                ? VoucherQuery::whereExpired($builder)
                : VoucherQuery::whereNotExpired($builder);
        }

        if ($q = $this->getFilter('q')) {
            $builder = VoucherQuery::whereSearchSponsorQuery($builder, $q);
        }

        $this->filterByImplementation($builder);
        $this->filterByGranted($builder);
        $this->filterByDates($builder);
        $this->filterByAmount($builder);
        $this->filterByUnassigned($builder);
        $this->filterByTypeAndSource($builder);
        $this->filterByInUse($builder);
        $this->filterByHasPayouts($builder);
        $this->filterByCountPerIdentity($builder);
        $this->filterByStateAndExpired($builder);
        $this->filterByIdentity($builder);

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'asc')
        );
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByAmount(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->hasFilter('amount_min')) {
            $builder->where('amount', '>=', $this->getFilter('amount_min'));
        }

        if ($this->hasFilter('amount_max')) {
            $builder->where('amount', '<=', $this->getFilter('amount_max'));
        }

        if ($this->hasFilter('amount_available_min') || $this->hasFilter('amount_available_max')) {
            $builder = VoucherQuery::addBalanceFields($builder);
            $builder = Voucher::query()->fromSub($builder, 'vouchers');
        }

        if ($this->hasFilter('amount_available_min')) {
            $builder->where('balance', '>=', $this->getFilter('amount_available_min'));
        }

        if ($this->hasFilter('amount_available_max')) {
            $builder->where('balance', '<=', $this->getFilter('amount_available_max'));
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByDates(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->hasFilter('from')) {
            $builder->where('created_at', '>=', Carbon::parse($this->getFilter('from'))->startOfDay());
        }

        if ($this->hasFilter('to')) {
            $builder->where('created_at', '<=', Carbon::parse($this->getFilter('to'))->endOfDay());
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByGranted(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        $granted = $this->getFilter('granted');

        if ($granted) {
            $builder->whereNotNull('identity_id');
        } elseif ($granted !== null) {
            $builder->whereNull('identity_id');
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByInUse(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        $in_use_from = $this->getFilter('in_use_from');
        $in_use_to = $this->getFilter('in_use_to');

        if ($this->hasFilter('in_use')) {
            $builder->where(function (Builder $builder) {
                if ($this->getFilter('in_use')) {
                    VoucherQuery::whereInUseQuery($builder);
                } else {
                    VoucherQuery::whereNotInUseQuery($builder);
                }
            });
        }

        if ($in_use_from || $in_use_to) {
            $builder = VoucherQuery::whereInUseDateQuery(
                VoucherSubQuery::appendFirstUseFields($builder),
                $in_use_from ? Carbon::parse($in_use_from)->startOfDay() : null,
                $in_use_to ? Carbon::parse($in_use_to)->endOfDay() : null,
            );
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByUnassigned(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->getFilter('unassigned')) {
            $builder->whereNull('identity_id');
        } elseif ($this->getFilter('unassigned') !== null) {
            $builder->whereNotNull('identity_id');
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByCountPerIdentity(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        $count_per_identity_min = $this->getFilter('count_per_identity_min');
        $count_per_identity_max = $this->getFilter('count_per_identity_max');

        if ($count_per_identity_min) {
            $builder->whereHas('identity.vouchers', function (Builder $builder) {
                $builder->whereIn('vouchers.id', (clone $builder)->select('vouchers.id'));
            }, '>=', $count_per_identity_min);
        }

        if ($count_per_identity_max) {
            $builder->whereHas('identity.vouchers', function (Builder $builder) {
                $builder->whereIn('vouchers.id', (clone $builder)->select('vouchers.id'));
            }, '<=', $count_per_identity_max);
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByHasPayouts(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->hasFilter('has_payouts')) {
            $builder->where(function (Builder $builder) {
                if ($this->getFilter('has_payouts')) {
                    $builder->whereHas('paid_out_transactions');
                } else {
                    $builder->whereDoesntHave('paid_out_transactions');
                }
            });
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByTypeAndSource(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        match ($this->getFilter('type')) {
            'all' => $builder,
            'fund_voucher' => $builder->whereNull('product_id'),
            'product_voucher' => $builder->whereNotNull('product_id'),
            default => abort(403),
        };

        match ($this->getFilter('source', 'employee')) {
            'all' => $builder,
            'user' => $builder->whereNull('employee_id'),
            'employee' => $builder->whereNotNull('employee_id'),
            default => abort(403),
        };

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Voucher|Builder|Relation
     */
    protected function filterByStateAndExpired(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->getFilter('state') === 'expired') {
            VoucherQuery::whereExpired($builder);
        }

        if ($this->getFilter('state') && $this->getFilter('state') !== 'expired') {
            VoucherQuery::whereNotExpired($builder->where('state', $this->getFilter('state')));
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function filterByImplementation(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->getFilter('implementation_id')) {
            $builder->whereRelation('fund.fund_config', [
                'implementation_id' => $this->getFilter('implementation_id'),
            ]);
        }

        if ($this->getFilter('implementation_key')) {
            $builder->whereRelation('fund.fund_config.implementation', [
                'key' => $this->getFilter('implementation_key'),
            ]);
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Voucher|Builder|Relation
     */
    protected function filterByIdentity(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        if ($this->hasFilter('email') && $email = $this->getFilter('email')) {
            $builder->where('identity_id', Identity::findByEmail($email)?->id ?: '_');
        }

        if ($this->getFilter('identity_id', false)) {
            $builder->where('identity_id', $this->getFilter('identity_id'));
        }

        if ($this->hasFilter('bsn') && $bsn = $this->getFilter('bsn')) {
            $builder->where(static function (Builder $builder) use ($bsn) {
                $builder->where('identity_id', Identity::findByBsn($bsn)?->id ?: '-');
                $builder->orWhereHas('voucher_relation', function (Builder $builder) use ($bsn) {
                    $builder->where(compact('bsn'));
                });
            });
        }

        return $builder;
    }

    /**
     * @param Relation|Builder|Voucher $builder
     * @return Relation|Builder|Voucher
     */
    protected function order(Relation|Builder|Voucher $builder): Relation|Builder|Voucher
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        if ($orderBy == 'voucher_type') {
            $builder->addSelect([
                'voucher_type' => Product::query()
                    ->whereColumn('id', 'vouchers.product_id')
                    ->selectRaw('if(count(*) > 0, "product", "regular")'),
            ]);
        }

        return $builder->orderBy($orderBy, $orderDir)->latest('created_at')->latest('id');
    }
}
