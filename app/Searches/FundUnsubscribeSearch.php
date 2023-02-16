<?php

namespace App\Searches;

use App\Models\FundUnsubscribe;
use App\Models\Organization;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FundUnsubscribeSearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: FundUnsubscribe::query());
    }

    /**
     * @return Builder|ProductCategory
     */
    public function query(): ?Builder
    {
        $query = parent::query();

        /** @var Organization $provider_organization */
        /** @var Organization $sponsor_organization */

        if ($this->hasFilter('provider_organization') && $provider_organization = $this->getFilter('provider_organization')) {
            $query->whereIn('fund_provider_id', $provider_organization->fund_providers->pluck('id')->toArray());
        }

        if ($this->hasFilter('sponsor_organization') && $sponsor_organization = $this->getFilter('sponsor_organization')) {
            $query->whereHas('fund_provider.fund', function(Builder $builder) use ($sponsor_organization) {
                return $builder->where('organization_id', $sponsor_organization->id);
            });
        }

        if ($this->hasFilter('state') && $state = $this->getFilter('state')) {
            if (in_array($state, [FundUnsubscribe::STATE_APPROVED, FundUnsubscribe::STATE_PENDING, FundUnsubscribe::STATE_CANCELED])) {
                $query->where('state', $state);
            } elseif ($state == 'expired') {
                $query->where(
                    'state', FundUnsubscribe::STATE_PENDING
                )->where(
                    'unsubscribe_date', '<=', now()
                );
            }
        }

        if ($this->hasFilter('states') && $states = $this->getFilter('states')) {
            $query->whereIn('state', $states);
        }

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $query->whereHas('fund_provider.fund', function(Builder $builder) use ($q) {
                return $builder->where('name', 'like', "%$q%");
            })->orWhereHas('fund_provider.fund.organization', function(Builder $builder) use ($q) {
                return $builder->where('name', 'like', "%$q%");
            });
        }

        if ($this->hasFilter('fund_id') && $fund_id = $this->getFilter('fund_id')) {
            $query->whereHas('fund_provider.fund', function(Builder $builder) use ($fund_id) {
                return $builder->where('id', $fund_id);
            });
        }

        if ($this->hasFilter('from') && $from = $this->getFilter('from')) {
            $query->where('unsubscribe_date', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($this->hasFilter('to') && $to = $this->getFilter('to')) {
            $query->where('unsubscribe_date', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }
}