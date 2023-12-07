<?php


namespace App\Searches;


use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;

class FundSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: Fund::query());
    }

    /**
     * @return FundRequest|Builder
     */
    public function query(): ?Builder
    {
        /** @var Fund|Builder $builder */
        $builder = parent::query();

        if (!$this->getFilter('with_archived', false)) {
            $builder->where('archived', false);
        }

        if (!$this->getFilter('with_external', false)) {
            $builder->where('type', '!=', Fund::TYPE_EXTERNAL);
        }

        if ($this->getFilter('configured', false)) {
            FundQuery::whereIsConfiguredByForus($builder);
        }

        if ($tag = $this->getFilter('tag')) {
            $builder->whereHas('tags_provider', fn (Builder $q) => $q->where('key', $tag));
        }

        if ($tagId = $this->getFilter('tag_id')) {
            $builder->whereHas('tags_webshop', fn (Builder $q) => $q->where('tags.id', $tagId));
        }

        if ($this->getFilter('organization_id')) {
            $builder->where('organization_id', $this->getFilter('organization_id'));
        }

        if ($this->getFilter('fund_id')) {
            $builder->where('id', $this->getFilter('fund_id'));
        }

        if (is_array($this->getFilter('fund_ids'))) {
            $builder->whereIn('id', $this->getFilter('fund_ids'));
        }

        if ($this->getFilter('q')) {
            FundQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        if ($this->getFilter('implementation_id')) {
            $builder->whereRelation('fund_config', 'implementation_id', $this->getFilter('implementation_id'));
        }

        if ($this->hasFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if (!is_null($this->getFilter('has_products'))) {
            $this->filterByApproval($builder, (bool) $this->getFilter('has_products'), 'product');
        }

        if (!is_null($this->getFilter('has_subsidies'))) {
            $this->filterByApproval($builder, (bool) $this->getFilter('has_subsidies'), 'subsidy');
        }

        if (!is_null($this->getFilter('has_providers'))) {
            $this->filterByApproval($builder, (bool) $this->getFilter('has_providers'));
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Fund $builder
     * @param bool $approved
     * @param string|null $type
     * @return void
     */
    protected function filterByApproval(Builder|Fund $builder, bool $approved, ?string $type = null): void
    {
        $funds = (clone $builder)->pluck('id')->toArray();

        if ($type === 'subsidy') {
            $builder->where('type', Fund::TYPE_SUBSIDIES);
        }

        if ($type === 'product') {
            $builder->where('type', Fund::TYPE_BUDGET);
        }

        if ($approved) {
            $builder->whereHas('fund_providers', function(Builder $builder) use ($funds, $type) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $funds, $type);
            });
        } else {
            $builder->whereDoesntHave('fund_providers', function(Builder $builder) use ($funds, $type) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $funds, $type);
            });
        }
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function order(Builder $builder): Builder
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        if ($orderBy === 'organization_name') {
            $builder->addSelect([
                'organization_name' => Organization::query()
                    ->whereColumn('id', 'organization_id')
                    ->select('name'),
            ]);
        }

        return Fund::query()
            ->fromSub($builder, 'funds')
            ->orderBy($orderBy, $orderDir)
            ->latest('created_at');
    }
}