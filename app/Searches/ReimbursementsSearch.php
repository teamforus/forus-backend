<?php


namespace App\Searches;

use App\Models\Reimbursement;
use App\Scopes\Builders\ReimbursementQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class ReimbursementsSearch extends BaseSearch
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
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        /** @var Builder|Reimbursement $builder */
        $builder = parent::query();

        if ($this->hasFilter('fund_id')) {
            $builder->whereRelation('voucher', 'fund_id', $this->getFilter('fund_id'));
        }

        if ($this->hasFilter('from')) {
            $builder->where('created_at', '>=', Carbon::parse($this->getFilter('from'))->startOfDay());
        }

        if ($this->hasFilter('to')) {
            $builder->where('created_at', '<=', Carbon::parse($this->getFilter('to'))->endOfDay());
        }

        if ($this->hasFilter('amount_min')) {
            $builder->where('amount', '>=', $this->getFilter('amount_min'));
        }

        if ($this->hasFilter('amount_max')) {
            $builder->where('amount', '<=', $this->getFilter('amount_max'));
        }

        if ($this->hasFilter('identity_address')) {
            $builder->whereRelation('voucher', 'identity_address', $this->getFilter('identity_address'));
        }

        $this->filterByStateAndExpiration($builder);
        $this->filterByQueryString($builder);

        return $builder;
    }

    /**
     * @param Builder|Relation|Reimbursement $builder
     * @return Reimbursement|Builder|Relation
     */
    protected function filterByStateAndExpiration(
        Builder|Relation|Reimbursement $builder
    ): Builder|Relation|Reimbursement {
        if ($this->hasFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('archived') && $this->getFilter('archived')) {
            ReimbursementQuery::whereArchived($builder);
        }

        if ($this->hasFilter('archived') && !$this->getFilter('archived')) {
            ReimbursementQuery::whereNotArchived($builder);
        }

        if ($this->hasFilter('deactivated') && $this->getFilter('deactivated')) {
            ReimbursementQuery::whereDeactivated($builder);
        }

        if ($this->hasFilter('deactivated') && !$this->getFilter('deactivated')) {
            ReimbursementQuery::whereNotDeactivated($builder);
        }

        if ($this->hasFilter('expired') && $this->getFilter('expired')) {
            ReimbursementQuery::whereExpired($builder);
        }

        if ($this->hasFilter('expired') && !$this->getFilter('expired')) {
            ReimbursementQuery::whereNotExpired($builder);
        }

        return $builder;
    }

    /**
     * @param Builder|Relation|Reimbursement $builder
     * @return Reimbursement|Builder|Relation
     */
    protected function filterByQueryString(
        Builder|Relation|Reimbursement $builder
    ): Builder|Relation|Reimbursement {
        if ($this->hasFilter('q') && $this->getFilter('q')) {
            return $builder->whereHas('voucher.identity', function(Builder $builder) {
                $q = $this->getFilter('q');
                $builder->whereRelation('primary_email', 'email', 'LIKE', "%$q%");
                $builder->orWhereRelation('record_bsn', 'value', 'LIKE', "%$q%");
            });
        }

        return $builder;
    }
}