<?php


namespace App\Searches\Sponsor;


use App\Models\Fund;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class FundIdentitiesSearch extends BaseSearch
{
    /**
     * FundIdentitiesSearch constructor.
     * @param array $filters
     * @param Fund $fund
     * @param bool $appendCounts
     */
    public function __construct(
        array $filters,
        Fund $fund,
        bool $appendCounts = false,
    ) {
        $withBalance = Arr::get($filters, 'target', 'all') === 'has_balance';
        $withEmail = Arr::get($filters, 'has_email', true);
        $withReservations = Arr::get($filters, 'with_reservations', false);
        $builder = $fund->activeIdentityQuery($withBalance, $withEmail);

        if ($appendCounts) {
            IdentityQuery::appendVouchersCountFields($builder, $fund, $withReservations);
        }

        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($this->hasFilter('q') && $this->getFilter('q')) {
            $builder->having('email', 'like', "%" . $this->getFilter('q') . "%");
        }

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc')
        );
    }
}