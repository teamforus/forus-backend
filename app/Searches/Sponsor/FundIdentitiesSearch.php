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
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Fund $fund
     */
    public function __construct(array $filters, Fund $fund)
    {
        $withBalance = Arr::get($filters, 'target', 'all') === 'has_balance';
        $withEmail = Arr::get($filters, 'has_email', true);
        $builder = $fund->activeIdentityQuery($withBalance, $withEmail);

        IdentityQuery::appendVouchersCountFields($builder, $fund);
        IdentityQuery::appendEmailField($builder);

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