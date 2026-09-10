<?php

namespace Tests\Feature;

use App\Models\FundProvider;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ProductInformationalTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductInformationalCanNotBeReserved(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, identity: $identity);

        // find product that can be reserved (price type will be not informational)
        $product = $this->findProductForReservation($voucher);
        $this->assertFalse($product->price_type === Product::PRICE_TYPE_INFORMATIONAL);

        // set as informational and assert validation error when creating reservation
        $product->update(['price_type' => Product::PRICE_TYPE_INFORMATIONAL]);

        $response = $this->makeReservationStoreRequest($voucher, $product);
        $response->assertJsonValidationErrorFor('product_id');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductStoreValidationPriceOptionalForInformationalPriceType(): void
    {
        $fundProvider = $this->setupFundProvider();
        $provider = $fundProvider->organization;

        $productData = [
            'name' => $this->faker->text(16),
            'description' => $this->faker->text(512),
            'price_type' => Product::PRICE_TYPE_REGULAR,
            'product_category_id' => ProductCategory::inRandomOrder()->first()->id,
        ];

        $this
            ->apiMakeProductRequest($provider, $productData, $provider->identity)
            ->assertJsonValidationErrors(['price', 'total_amount']);

        $response = $this
            ->apiMakeProductRequest($provider, [
                ...$productData,
                'price_type' => Product::PRICE_TYPE_INFORMATIONAL,
            ], $provider->identity)
            ->assertSuccessful();

        $product = $provider->products()->findOrFail($response->json('data.id'));
        $this->assertEquals(Product::PRICE_TYPE_INFORMATIONAL, $product->price_type);
    }

    /**
     * @return FundProvider
     */
    protected function setupFundProvider(): FundProvider
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);

        return $this->apiApplyProviderToFund($provider, $fund, $provider->identity);
    }
}
