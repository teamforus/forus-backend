<?php

namespace App\Searches;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProviderUnsubscribe;
use App\Models\ProductCategory;
use App\Scopes\Builders\FundProviderUnsubscribeQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class FundUnsubscribeSearch extends BaseSearch
{
    /**
     * @return Builder|ProductCategory
     */
    public function query(): ?Builder
    {
        /** @var Builder|Relation|FundProviderUnsubscribe $query */
        $query = parent::getBuilder();

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $query->whereHas('fund_provider.fund', function(Builder $builder) use ($q) {
                return $builder->where('name', 'like', "%$q%");
            })->orWhereHas('fund_provider.fund.organization', function(Builder $builder) use ($q) {
                return $builder->where('name', 'like', "%$q%");
            });
        }

        if ($this->hasFilter('state')) {
            $query = $this->whereState($query, $this->getFilter('state'));
        }

        if ($this->hasFilter('fund_id')) {
            $query->whereRelation('fund_provider', 'fund_id', $this->getFilter('fund_id'));
        }

        if ($this->hasFilter('from') && $from = $this->getFilter('from')) {
            $query->where('unsubscribe_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($this->hasFilter('to') && $to = $this->getFilter('to')) {
            $query->where('unsubscribe_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @return string[]
     */
    public static function rules(BaseFormRequest $request = null): array
    {
        return [
            'q'         => 'nullable|string|max:100',
            'fund_id'   => 'nullable|exists:funds,id',
            'from'      => 'nullable|date:Y-m-d',
            'to'        => 'nullable|date:Y-m-d',
            'state'     => 'nullable|in:' . implode(',', FundProviderUnsubscribe::STATES),
            'per_page'  => $request->perPageRule(1000),
        ];
    }

    /**
     * @param FundProviderUnsubscribe|Relation|Builder $builder
     * @param string $state
     * @return Builder|Relation|FundProviderUnsubscribe
     */
    private function whereState(
        FundProviderUnsubscribe|Relation|Builder $builder,
        string $state = 'pending'
    ): Builder|Relation|FundProviderUnsubscribe {
        return match ($state) {
            'pending' => $builder->where(function(Builder $builder) {
                $builder->where(fn (Builder $builder) => FundProviderUnsubscribeQuery::wherePending($builder));
                $builder->orWhere(fn (Builder $builder) => FundProviderUnsubscribeQuery::whereOverdue($builder));
            }),
            'approved' => FundProviderUnsubscribeQuery::whereApproved($builder),
            'canceled' => FundProviderUnsubscribeQuery::whereCanceled($builder),
            default => $builder,
        };
    }
}