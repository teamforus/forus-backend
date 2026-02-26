<?php

namespace Tests\Unit\Searches;

use App\Models\BusinessType;
use App\Searches\BusinessTypeSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class BusinessTypeSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFundProviders;
    use MakesTestFunds;
    use MakesTestOrganizations;

    public function testQueryBuilds(): void
    {
        $search = new BusinessTypeSearch([], BusinessType::query());

        $this->assertQueryBuilds($search->query());
    }

    public function testFiltersByUsed(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $providerProducts = $this->makeTestProviderWithProducts(1);
        $providerProduct = $providerProducts[0];
        $this->addProductToFund($fund, $providerProduct, false);

        $usedType = BusinessType::find($providerProduct->organization->business_type_id);
        $this->assertNotNull($usedType);

        $unusedType = BusinessType::create([
            'key' => 'unused_type',
        ]);

        $unusedOrganization = $this->makeTestOrganization($this->makeIdentity(), [
            'business_type_id' => $unusedType->id,
        ]);

        $search = new BusinessTypeSearch(['used' => true], BusinessType::query());
        $ids = $search->query()->pluck('id')->toArray();

        $this->assertContains($usedType->id, $ids);
        $this->assertNotContains($unusedOrganization->business_type_id, $ids);
    }
}
