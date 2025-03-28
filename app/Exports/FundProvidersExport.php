<?php

namespace App\Exports;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FundProvidersExport extends BaseFieldedExport
{

    protected static string $transKey = 'providers';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'fund',
        'implementation',
        'fund_type',
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
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(
        Request $request,
        Organization $organization,
        protected array $fields
    ) {
        $this->data = $this->export($request, $organization);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Collection
     */
    protected function export(Request $request, Organization $organization): Collection
    {
        $data = FundProvider::search($request, $organization)->with([
            'fund.fund_config.implementation',
            'organization.last_employee_session',
        ])->get();

        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (FundProvider $fundProvider) => array_only(
            $this->getRow($fundProvider), $this->fields
        )));
    }

    /**
     * @param FundProvider $fundProvider
     * @return array
     */
    protected function getRow(FundProvider $fundProvider): array
    {
        $provider = $fundProvider->organization;
        $lastActivity = $fundProvider->getLastActivity();

        $providerProductsQuery = ProductQuery::whereNotExpired($provider->products_provider());
        $individualProductsQuery = $fundProvider->fund_provider_products()->whereHas('product');

        $sponsorProductsQuery = ProductQuery::whereNotExpired($provider->products_sponsor()->where([
            'sponsor_organization_id' => $fundProvider->fund->organization_id,
        ]));

        $activeProductsQuery = ProductQuery::approvedForFundsAndActiveFilter(
            $fundProvider->products()->getQuery(),
            $fundProvider->fund_id,
        );

        $result = DB::query()->select([
            'individual_products_count' => $individualProductsQuery->selectRaw('count(*)'),
            'provider_products_count' => $providerProductsQuery->selectRaw('count(*)'),
            'sponsor_products_count' => $sponsorProductsQuery->selectRaw('count(*)'),
            'active_products_count' => $activeProductsQuery->selectRaw('count(*)'),
        ])->first();

        $hasIndividualProducts = ($result->individual_products_count > 0 || $fundProvider->allow_products);

        return [
            'fund' => $fundProvider->fund->name,
            'implementation' => $fundProvider->fund->fund_config?->implementation?->name,
            'fund_type' => $fundProvider->fund->type,
            'provider' => $provider->name,
            'iban' => $provider->iban,
            'provider_last_activity' => $lastActivity?->diffForHumans(now()),
            'products_provider_count' => $result->provider_products_count,
            'products_sponsor_count' => $result->sponsor_products_count,
            'products_active_count' => $result->active_products_count,
            'products_count' => $result->provider_products_count + $result->sponsor_products_count,
            'phone' => $provider->phone,
            'email' => $provider->email,
            'kvk' => $fundProvider->organization->kvk,
            'state' => $fundProvider->state_locale,
            'allow_budget' => $fundProvider->allow_budget ? 'Ja' : 'Nee',
            'allow_products' => $fundProvider->allow_products ? 'Ja' : 'Nee',
            'allow_some_products' => $hasIndividualProducts ? 'Ja' : 'Nee',
        ];
    }
}
