<?php

namespace Tests\Traits;

use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;

trait MakesApiRequests
{
    use WithFaker;
    use HasDbTokens;
    use DoesTesting;
    use MakesTestProducts;
    use TestsReservations;

    /**
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeOrganizationRequest(array $data, Identity $identity): TestResponse
    {
        return $this->postJson('/api/v1/platform/organizations', $data, $this->makeApiHeaders($identity));
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return Organization
     */
    public function apiMakeOrganization(array $data, Identity $identity): Organization
    {
        $response = $this
            ->apiMakeOrganizationRequest([
                'name' => $this->faker->text(16),
                'iban' => $this->faker->iban('NL'),
                'email' => $this->makeUniqueEmail(),
                'phone' => '1234567890',
                'kvk' => '00000000',
                'business_type_id' => BusinessType::inRandomOrder()->first()->id,
                ...$data,
            ], $identity)
            ->assertSuccessful();

        return $identity->organizations()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeFundRequest(Organization $organization, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/funds",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return Fund
     */
    public function apiMakeFund(Organization $organization, array $data, Identity $identity): Fund
    {
        // workaround for start date having to be at least 5 days from now
        $now = now();
        $this->travelTo($now->copy()->subDays(6));

        $response = $this
            ->apiMakeFundRequest($organization, [
                'name' => $this->faker->text(16),
                'outcome_type' => 'voucher',
                'start_date' => $now->copy()->format('Y-m-d'),
                'end_date' => $now->copy()->addYear()->format('Y-m-d'),
                ...$data,
            ], $identity)
            ->assertSuccessful();

        $this->travelBack();

        return $organization->funds()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeProductRequest(Organization $organization, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/products",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return Product
     */
    public function apiMakeProduct(Organization $organization, array $data, Identity $identity): Product
    {
        $response = $this
            ->apiMakeProductRequest($organization, [
                'name' => $this->faker->text(16),
                'description' => $this->faker->text(512),
                'price_type' => 'regular',
                'price' => '10',
                'product_category_id' => ProductCategory::inRandomOrder()->first()->id,
                'total_amount' => 10,
                ...$data,
            ], $identity)
            ->assertSuccessful();

        return $organization->products()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiDeleteProductRequest(Organization $organization, Product $product, Identity $identity): TestResponse
    {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/products/$product->id",
            [],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiDeleteProduct(Organization $organization, Product $product, Identity $identity): TestResponse
    {
        return $this
            ->apiDeleteProductRequest($organization, $product, $identity)
            ->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiApplyProviderToFundRequest(Organization $organization, Fund $fund, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/provider/funds",
            ['fund_id' => $fund->id],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiApplyProviderToFund(Organization $organization, Fund $fund, Identity $identity): FundProvider
    {
        $response = $this
            ->apiApplyProviderToFundRequest($organization, $fund, $identity)
            ->assertSuccessful();

        return $organization->fund_providers()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeVoucherAsSponsorRequest(Organization $organization, Fund $fund, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/vouchers",
            ['fund_id' => $fund->id, 'amount' => '1000', ...$data],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $data
     * @param Identity $identity
     * @return Voucher
     */
    public function apiMakeVoucherAsSponsor(Organization $organization, Fund $fund, array $data, Identity $identity): Voucher
    {
        $response = $this
            ->apiMakeVoucherAsSponsorRequest($organization, $fund, $data, $identity)
            ->assertSuccessful();

        return Voucher::where('id', $response->json('data.id'))->firstOrFail();
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiUpdateFundProviderRequest(
        Organization $organization,
        FundProvider $fundProvider,
        array $data,
        Identity $identity
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fundProvider->fund_id/providers/$fundProvider->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param array $data
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiUpdateFundProvider(
        Organization $organization,
        FundProvider $fundProvider,
        array $data,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiUpdateFundProviderRequest($organization, $fundProvider, $data, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherAsProviderRequest(
        Voucher $voucher,
        Identity $identity
    ): TestResponse {
        return $this->getJson(
            '/api/v1/platform/provider/vouchers/' . $voucher->token_without_confirmation->address,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherAsProvider(
        Voucher $voucher,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherAsProviderRequest($voucher, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherProductsAsProviderRequest(
        Voucher $voucher,
        Identity $identity
    ): TestResponse {
        return $this->getJson(
            '/api/v1/platform/provider/vouchers/' . $voucher->token_without_confirmation->address . '/products',
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherProductsAsProvider(
        Voucher $voucher,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherProductVouchersAsProviderRequest(
        Voucher $voucher,
        Organization $organization,
        Identity $identity
    ): TestResponse {
        $address = $voucher->token_without_confirmation->address;

        return $this->getJson(
            "/api/v1/platform/provider/vouchers/$address/product-vouchers?organization_id=$organization->id",
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherProductVouchersAsProvider(
        Voucher $voucher,
        Organization $organization,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherProductVouchersAsProviderRequest($voucher, $organization, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeProductReservationRequest(
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->postJson(
            '/api/v1/platform/product-reservations',
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return ProductReservation
     */
    public function apiMakeProductReservation(
        array $data,
        Identity $identity
    ): ProductReservation {
        $response = $this
            ->apiMakeProductReservationRequest($data, $identity)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiCancelProductReservationRequest(
        ProductReservation $reservation,
        Identity $identity,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/product-reservations/$reservation->id/cancel",
            [],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @return ProductReservation
     */
    public function apiCancelProductReservation(
        ProductReservation $reservation,
        Identity $identity,
    ): ProductReservation {
        $response = $this
            ->apiCancelProductReservationRequest($reservation, $identity)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }
}
