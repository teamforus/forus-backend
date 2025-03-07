<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class OrganizationSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: Organization::query());
    }

    /**
     * @return Organization|Builder
     */
    public function query(): ?Builder
    {
        /** @var Organization|Builder $builder */
        $builder = parent::query();

        if ($this->getFilter('is_sponsor')) {
            $builder->where('is_sponsor', true);
        }

        if ($this->getFilter('is_provider')) {
            $builder->where('is_provider', true);
        }

        if ($this->getFilter('is_validator')) {
            $builder->where('is_validator', true);
        }

        if ($this->getFilter('has_reservations') && $this->getFilter('auth_address')) {
            $builder->whereHas('products.product_reservations', function (Builder $builder) {
                $builder->whereHas('voucher', function (Builder $builder) {
                    $builder->whereRelation('identity', 'address', $this->getFilter('auth_address'));
                });
            });
        }

        if ($this->getFilter('q')) {
            $builder = OrganizationQuery::queryFilterPublic($builder, $this->getFilter('q'));
        }

        $builder = match ($this->getFilter('type')) {
            'sponsor' => $this->whereHasFunds(
                $builder,
                $this->getFilter('implementation_id'),
            ),
            'provider' => $this->whereHasProducts(
                $builder,
                $this->getFilter('implementation_id'),
                $this->getFilter('fund_type', 'budget'),
            ),
            default => $builder->where(function (Builder $builder) {
                if ($this->getFilter('auth_address')) {
                    OrganizationQuery::whereIsEmployee($builder, $this->getFilter('auth_address'));
                } else {
                    $builder->whereIn('id', []);
                }
            }),
        };

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'asc'),
        );
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param int $implementationId
     * @return Builder|Relation|Organization
     */
    protected function whereHasFunds(
        Builder|Relation|Organization $builder,
        int $implementationId,
    ): Builder|Relation|Organization {
        return $builder->whereHas('funds', function (Builder $builder) use ($implementationId) {
            $builder = FundQuery::whereIsConfiguredByForus(FundQuery::whereActiveFilter($builder));
            $builder->whereRelation('fund_config', 'implementation_id', $implementationId);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param int $implementationId
     * @param string $fundType
     * @return Builder|Relation|Organization
     */
    protected function whereHasProducts(
        Builder|Relation|Organization $builder,
        int $implementationId,
        string $fundType,
    ): Builder|Relation|Organization {
        return $builder->whereHas('products', function (Builder $builder) use ($fundType, $implementationId) {
            $activeFunds = Implementation::find($implementationId)->funds()->where('type', $fundType);
            $activeFunds = FundQuery::whereIsConfiguredByForus(FundQuery::whereActiveFilter($activeFunds));

            // only in stock and not expired
            $builder = ProductQuery::inStockAndActiveFilter($builder->select('id'));

            // only approved by at least one sponsor
            return ProductQuery::approvedForFundsFilter($builder, $activeFunds->pluck('funds.id')->toArray());
        });
    }
}
