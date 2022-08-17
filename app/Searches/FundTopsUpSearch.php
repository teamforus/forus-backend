<?php


namespace App\Searches;


use App\Models\FundTopUp;
use App\Scopes\Builders\ProductReservationQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FundTopsUpSearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: FundTopUp::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $query) use ($q) {
                $query->whereRelation('bank_connection_account', 'monetary_account_iban', 'LIKE', "%$q%");
                $query->orWhereRelation('fund_top_up', 'code', 'LIKE', "%$q%");
            });
        }

        if ($this->hasFilter('amount_min')) {
            $builder->where('amount', '>=', $this->getFilter('amount_min'));
        }

        if ($this->hasFilter('amount_max')) {
            $builder->where('amount', '<=', $this->getFilter('amount_max'));
        }

        if ($this->hasFilter('from')) {
            $builder->where('created_at','>=', $this->getFilterDate('from')->startOfDay());
        }

        if ($this->hasFilter('to')) {
            $builder->where('created_at','<=', $this->getFilterDate('to')->endOfDay());
        }

        return $builder;
    }
}