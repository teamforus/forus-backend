<?php

namespace Tests\Feature;

use App\Models\Implementation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class FiltersVisibleProductsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;

    /**
     * Authenticated identity with a voucher on filtered fund should only see resources linked to that fund.
     *
     * @return void
     */
    public function testIdentityWithFilteredFundSeesOnlyFilteredResults(): void
    {
        [
            $filteredFund,
            $openFund,
            $filteredProduct,
            $openProduct,
            $filteredCategory,
            $openCategory,
            $filteredProvider,
            $openProvider,
        ] = $this->makeVisibilityFixture();

        $identity = $this->makeIdentity($this->makeUniqueEmail('recipient_'));
        $this->makeTestVoucher($filteredFund, $identity);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));

        // products
        $guestProductIds = Arr::pluck(
            $this->getJson('/api/v1/platform/products?per_page=50')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProduct->id, $guestProductIds);
        $this->assertContains($openProduct->id, $guestProductIds);

        $filteredProductIds = Arr::pluck(
            $this->getJson('/api/v1/platform/products?per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProduct->id, $filteredProductIds);
        $this->assertNotContains($openProduct->id, $filteredProductIds);

        // funds with providers
        $guestFundIds = Arr::pluck(
            $this->getJson('/api/v1/platform/funds?has_providers=1&per_page=50')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredFund->id, $guestFundIds);
        $this->assertContains($openFund->id, $guestFundIds);

        $filteredFundIds = Arr::pluck(
            $this->getJson('/api/v1/platform/funds?has_providers=1&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredFund->id, $filteredFundIds);
        $this->assertNotContains($openFund->id, $filteredFundIds);

        // product categories
        $guestCategoryIds = Arr::pluck(
            $this->getJson('/api/v1/platform/product-categories?used=1&parent_id=null&per_page=100')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredCategory->id, $guestCategoryIds);
        $this->assertContains($openCategory->id, $guestCategoryIds);

        $filteredCategoryIds = Arr::pluck(
            $this->getJson('/api/v1/platform/product-categories?used=1&parent_id=null&per_page=100', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredCategory->id, $filteredCategoryIds);
        $this->assertNotContains($openCategory->id, $filteredCategoryIds);

        // business types
        $guestBusinessTypeIds = Arr::pluck(
            $this->getJson('/api/v1/platform/business-types?used=1&parent_id=null&per_page=50')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->business_type_id, $guestBusinessTypeIds);
        $this->assertContains($openProvider->business_type_id, $guestBusinessTypeIds);

        $filteredBusinessTypeIds = Arr::pluck(
            $this->getJson('/api/v1/platform/business-types?used=1&parent_id=null&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->business_type_id, $filteredBusinessTypeIds);

        if ($filteredProvider->business_type_id !== $openProvider->business_type_id) {
            $this->assertNotContains($openProvider->business_type_id, $filteredBusinessTypeIds);
        }

        // organizations index
        $guestProviderIds = Arr::pluck(
            $this->getJson('/api/v1/platform/organizations?type=provider&per_page=50')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $guestProviderIds);
        $this->assertContains($openProvider->id, $guestProviderIds);

        $filteredProviderIds = Arr::pluck(
            $this->getJson('/api/v1/platform/organizations?type=provider&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $filteredProviderIds);
        $this->assertNotContains($openProvider->id, $filteredProviderIds);

        // providers index
        $guestProvidersIndexIds = Arr::pluck(
            $this->getJson('/api/v1/platform/providers?per_page=50')->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $guestProvidersIndexIds);
        $this->assertContains($openProvider->id, $guestProvidersIndexIds);

        $filteredProvidersIndexIds = Arr::pluck(
            $this->getJson('/api/v1/platform/providers?per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $filteredProvidersIndexIds);
        $this->assertNotContains($openProvider->id, $filteredProvidersIndexIds);
    }

    /**
     * Authenticated identity without vouchers on filtered funds should still see all resources.
     *
     * @return void
     */
    public function testIdentityWithoutFilteredFundKeepsAllResults(): void
    {
        [
            $filteredFund,
            $openFund,
            $filteredProduct,
            $openProduct,
            $filteredCategory,
            $openCategory,
            $filteredProvider,
            $openProvider,
        ] = $this->makeVisibilityFixture();

        $identity = $this->makeIdentity($this->makeUniqueEmail('recipient_'));
        $this->makeTestVoucher($openFund, $identity);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));

        // products
        $productIds = Arr::pluck(
            $this->getJson('/api/v1/platform/products?per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProduct->id, $productIds);
        $this->assertContains($openProduct->id, $productIds);

        // funds with providers
        $fundIds = Arr::pluck(
            $this->getJson('/api/v1/platform/funds?has_providers=1&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredFund->id, $fundIds);
        $this->assertContains($openFund->id, $fundIds);

        // product categories
        $categoryIds = Arr::pluck(
            $this->getJson('/api/v1/platform/product-categories?used=1&parent_id=null&per_page=100', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredCategory->id, $categoryIds);
        $this->assertContains($openCategory->id, $categoryIds);

        // business types
        $businessTypeIds = Arr::pluck(
            $this->getJson('/api/v1/platform/business-types?used=1&parent_id=null&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->business_type_id, $businessTypeIds);

        if ($filteredProvider->business_type_id !== $openProvider->business_type_id) {
            $this->assertContains($openProvider->business_type_id, $businessTypeIds);
        }

        // organizations index
        $providerIds = Arr::pluck(
            $this->getJson('/api/v1/platform/organizations?type=provider&per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $providerIds);
        $this->assertContains($openProvider->id, $providerIds);

        // providers index
        $providersIndexIds = Arr::pluck(
            $this->getJson('/api/v1/platform/providers?per_page=50', $headers)->assertSuccessful()->json('data'),
            'id',
        );

        $this->assertContains($filteredProvider->id, $providersIndexIds);
        $this->assertContains($openProvider->id, $providersIndexIds);
    }

    /**
     * @return array
     */
    protected function makeVisibilityFixture(): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $implementation = Implementation::general();

        $filteredFund = $this->makeTestFund(
            $sponsor,
            [],
            ['filters_visible_products' => true],
            $implementation,
        );

        $openFund = $this->makeTestFund(
            $sponsor,
            [],
            ['filters_visible_products' => false],
            $implementation,
        );

        $filteredProvider = $this->makeTestProviderOrganization($this->makeIdentity());
        $filteredCategory = $this->makeProductCategory();
        $filteredProduct = $this->makeTestProduct($filteredProvider, 10, $filteredCategory->id);

        $openProvider = $this->makeTestProviderOrganization($this->makeIdentity());
        $openCategory = $this->makeProductCategory();
        $openProduct = $this->makeTestProduct($openProvider, 10, $openCategory->id);

        $this->addProductToFund($openFund, $openProduct, false);
        $this->addProductToFund($filteredFund, $filteredProduct, false);

        return [
            $filteredFund,
            $openFund,
            $filteredProduct,
            $openProduct,
            $filteredCategory,
            $openCategory,
            $filteredProvider,
            $openProvider,
        ];
    }
}
