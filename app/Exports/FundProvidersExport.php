<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\FundProvider;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FundProvidersExport extends BaseExport
{
    protected static string $transKey = 'providers';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'fund',
        'implementation',
        'provider',
        'iban',
        'provider_last_activity',
        'products_provider_count',
        'products_sponsor_count',
        'products_active_count',
        'products_count',
        'phone',
        'email',
        'kvk',
        'state',
        'allow_budget',
        'allow_products',
        'allow_some_products',
    ];

    /**
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'fund.fund_config.implementation',
        'organization.last_employee_session',
    ];

    /**
     * @param Model|FundProvider $model
     * @return array
     */
    protected function getRow(Model|FundProvider $model): array
    {
        $provider = $model->organization;
        $lastActivity = $model->getLastActivity();

        $providerProductsQuery = ProductQuery::whereNotExpired($provider->products_provider());
        $individualProductsQuery = $model->fund_provider_products()->whereHas('product');

        $sponsorProductsQuery = ProductQuery::whereNotExpired($provider->products_sponsor()->where([
            'sponsor_organization_id' => $model->fund->organization_id,
        ]));

        $activeProductsQuery = ProductQuery::approvedForFundsAndActiveFilter(
            $model->products()->getQuery(),
            $model->fund_id,
        );

        $result = DB::query()->select([
            'individual_products_count' => $individualProductsQuery->selectRaw('count(*)'),
            'provider_products_count' => $providerProductsQuery->selectRaw('count(*)'),
            'sponsor_products_count' => $sponsorProductsQuery->selectRaw('count(*)'),
            'active_products_count' => $activeProductsQuery->selectRaw('count(*)'),
        ])->first();

        $hasIndividualProducts = ($result->individual_products_count > 0 || $model->allow_products);

        return [
            'fund' => $model->fund->name,
            'implementation' => $model->fund->fund_config?->implementation?->name,
            'provider' => $provider->name,
            'iban' => $provider->iban,
            'provider_last_activity' => $lastActivity?->diffForHumans(now()),
            'products_provider_count' => $result->provider_products_count,
            'products_sponsor_count' => $result->sponsor_products_count,
            'products_active_count' => $result->active_products_count,
            'products_count' => $result->provider_products_count + $result->sponsor_products_count,
            'phone' => $provider->phone,
            'email' => $provider->email,
            'kvk' => $model->organization->kvk,
            'state' => $model->state_locale,
            'allow_budget' => $model->allow_budget ? 'Ja' : 'Nee',
            'allow_products' => $model->allow_products ? 'Ja' : 'Nee',
            'allow_some_products' => $hasIndividualProducts ? 'Ja' : 'Nee',
        ];
    }
}
