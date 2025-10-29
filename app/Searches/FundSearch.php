<?php

namespace App\Searches;

use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Fund $builder
     */
    public function __construct(array $filters, Builder|Relation|Fund $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Fund
     */
    public function query(): Builder|Relation|Fund
    {
        /** @var Builder|Relation|Fund $builder */
        $builder = parent::query();

        if (!$this->getFilter('with_archived', false)) {
            $builder->where('archived', false);
        }

        if (!$this->getFilter('with_external', false)) {
            $builder->where('external', false);
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

        if ($this->getFilter('physical_card_type_id')) {
            $builder->whereRelation('physical_card_types', 'physical_card_types.id', $this->getFilter('physical_card_type_id'));
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

        if ($this->getFilter('state')) {
            $builder->whereIn('state', (array) $this->getFilter('state'));
        }

        if (!is_null($this->getFilter('has_products'))) {
            $this->filterByApproval($builder, (bool) $this->getFilter('has_products'));
        }

        if (!is_null($this->getFilter('has_providers'))) {
            $this->filterByApproval($builder, (bool) $this->getFilter('has_providers'));
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|Fund $builder
     * @param bool $approved
     * @return void
     */
    protected function filterByApproval(Builder|Relation|Fund $builder, bool $approved): void
    {
        $funds = (clone $builder)->pluck('id')->toArray();

        if ($approved) {
            $builder->whereHas('fund_providers', function (Builder $builder) use ($funds) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $funds);
            });
        } else {
            $builder->whereDoesntHave('fund_providers', function (Builder $builder) use ($funds) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $funds);
            });
        }
    }

    /**
     * @param Builder|Relation|Fund $builder
     * @return Builder|Relation|Fund
     */
    protected function order(Builder|Relation|Fund $builder): Builder|Relation|Fund
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
            ->oldest('created_at');
    }
}
