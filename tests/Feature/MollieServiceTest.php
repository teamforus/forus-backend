<?php

namespace Feature;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Services\MollieService\Models\MollieConnection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class MollieServiceTest extends TestCase
{
    use MakesTestOrganizations, MakesTestFunds, DatabaseTransactions, WithFaker;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s';

    /**
     * @var string
     */
    protected string $apiFundProviderUrl = '/api/v1/platform/organizations/%s/funds/%s/providers/%s';

    /**
     * @var string
     */
    protected string $apiReservationUrl = '/api/v1/platform/product-reservations';

    /**
     * @var string
     */
    protected string $apiProviderReservationUrl = '/api/v1/platform/organizations/%s/product-reservations';

    /**
     * @return FundProvider
     * @throws \Throwable
     */
    public function prepareFundProvider(): FundProvider
    {
        $organization = $this->getOrganization();

        /** @var Fund $fund */
        $fund = $organization->funds->first();
        $this->assertNotNull($fund);

        $voucher = $fund->makeVoucher($organization->identity_address, [
            'state' => Voucher::STATE_ACTIVE
        ], 100);

        $this->assertNotNull($voucher);

        $provider = $this->getProviderOrganization();
        $product = $this->createProductForReservation($provider, $fund, ['price' => 120]);

        /** @var FundProvider|null $fundProvider */
        $fundProvider = $product->fund_providers()->where('fund_id', $fund->id)->first();
        $this->assertNotNull($fundProvider);

        $this->assertFalse($fundProvider->allow_extra_payments);

        $this->updateFundProvider($organization, $fund, $fundProvider);

        return $fundProvider;
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param bool $allowExtraPayments
     * @return void
     */
    private function updateFundProvider(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        bool $allowExtraPayments = true
    ): void {
        $this->assertNotNull($organization->identity);
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $response = $this->patchJson(sprintf($this->apiFundProviderUrl, $organization->id, $fund->id, $fundProvider->id), [
            'allow_extra_payments' => $allowExtraPayments
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => $allowExtraPayments]);
        $fundProvider->refresh();
        $this->assertEquals($allowExtraPayments, $fundProvider->allow_extra_payments);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testCreateMollieConnectionAccount(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $dateNow = now();

        $data = [
            'name' => $this->faker->name,
            'country_code' => $this->faker->countryCode,
            'profile_name' => $this->faker->name,
            'phone' => $this->faker->e164PhoneNumber,
            'website' => $this->faker->url,
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'street' => $this->faker->streetName,
            'city' => $this->faker->city,
            'postcode' => $this->faker->postcode,
        ];

        $this->assertNotNull($provider->identity);
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $data, $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testConnectMollieAccount(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $dateNow = now();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);
    }

    /**
     * @param Organization $provider
     * @param Carbon $dateNow
     * @return void
     */
    private function assertConnectionCreated(Organization $provider, Carbon $dateNow): void
    {
        $mollieConnection = MollieConnection::where('created_at', '>=', $dateNow)->first();
        $this->assertNotNull($mollieConnection);
        $this->assertEquals(MollieConnection::STATE_PENDING, $mollieConnection->connection_state);

        $code = token_generator()->generate(64);
        $response = $this->getJson("/mollie/callback?state=$mollieConnection->state_code&code=$code");

        $expectUrl = Implementation::general()->urlProviderDashboard(
            "/organizations/$mollieConnection->organization_id/payment-methods"
        );

        $response->assertRedirect($expectUrl);
        $mollieConnection->refresh();
        $this->assertEquals(MollieConnection::STATE_ACTIVE, $mollieConnection->connection_state);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection/fetch', $provider->id), $apiHeaders);

        $response->assertSuccessful();
        $mollieConnection->refresh();
        $this->assertEquals(MollieConnection::ONBOARDING_STATE_COMPLETED, $mollieConnection->onboarding_state);

        $this->assertTrue($provider->canReceiveExtraPayments());
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieConnectionAccountAccessAllowedByFund(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $apiHeaders);

        $response->assertSuccessful();
        $this->assertFalse($provider->canReceiveExtraPayments());
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieConnectionAccountAccessIfExist(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;
        $dateNow = now();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);
        $this->assertConnectionCreated($provider, $dateNow);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $response = $this->patchJson(sprintf($this->apiFundProviderUrl, $organization->id, $fund->id, $fundProvider->id), [
            'allow_extra_payments' => false
        ], $apiHeaders);
        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => false]);
        $provider->refresh();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $apiHeaders);

        $response->assertSuccessful();
        $this->assertFalse($provider->canReceiveExtraPayments());
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieConnectionAccountAccessForbidden(): void
    {
        $organization = $this->getOrganization();

        /** @var Fund $fund */
        $fund = $organization->funds->first();
        $this->assertNotNull($fund);

        $provider = $this->getProviderOrganization();
        $product = $this->createProductForReservation($provider, $fund, ['price' => 120]);

        /** @var FundProvider $fundProvider */
        $fundProvider = $product->fund_providers()->where('fund_id', $fund->id)->first();
        $this->assertNotNull($fundProvider);

        $this->assertFalse($fundProvider->allow_extra_payments);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $apiHeaders);

        $response->assertForbidden();
        $this->assertFalse($provider->canReceiveExtraPayments());
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieAccountReservationSuccess(): void
    {
        $this->makeReservation();
    }

    /**
     * @param bool $payExtra
     * @return ProductReservation
     * @throws \Throwable
     */
    private function makeReservation(bool $payExtra = true): ProductReservation
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;
        $dateNow = now();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()->first();
        /** @var Product $product */
        $product = $provider->products()->first();

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->apiReservationUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['checkout_url']);

        /** @var ProductReservation $reservation */
        $reservation = $voucher->product_reservations()->first();
        $this->assertNotNull($reservation);
        $this->assertNotNull($reservation->extra_payment?->payment_id);

        if ($payExtra) {
            $response = $this->postJson("/mollie/webhooks", [
                'id' => $reservation->extra_payment->payment_id
            ]);

            $response->assertSuccessful();

            $reservation->refresh();
            $this->assertTrue($reservation->extra_payment->isPaid());
        }

        return $reservation;
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationRejectNotPaidAndNotExpired(): void
    {
        $reservation = $this->makeReservation(false);
        $provider = $reservation->product->organization;

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertForbidden();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationRejectNotPaidAndExpired(): void
    {
        $reservation = $this->makeReservation(false);
        $reservation->extra_payment->update(['expires_at' => now()->subMinute()]);
        $provider = $reservation->product->organization;

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationRejectPaid(): void
    {
        $reservation = $this->makeReservation();
        $provider = $reservation->product->organization;

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertForbidden();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationRefundAndReject(): void
    {
        $reservation = $this->makeReservation();
        $provider = $reservation->product->organization;

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->getJson(
            sprintf($this->apiProviderReservationUrl . '/%s/extra-payments/refund', $provider->id, $reservation->id), $headers
        );

        $response->assertSuccessful();
        $reservation->refresh();

        $this->assertTrue($reservation->extra_payment->isFullyRefunded());

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationCancelPaidByRequester(): void
    {
        $reservation = $this->makeReservation();
        $identity = $reservation->voucher->identity;

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiReservationUrl . '/%s/cancel', $reservation->id), [], $headers
        );

        $response->assertForbidden();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationCancelNotPaidAndNotExpiredByRequester(): void
    {
        $reservation = $this->makeReservation(false);
        $identity = $reservation->voucher->identity;
        $this->assertNotNull($identity);

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiReservationUrl . '/%s/cancel', $reservation->id), [], $headers
        );

        $response->assertForbidden();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationCancelNotPaidAndExpiredByRequester(): void
    {
        $reservation = $this->makeReservation(false);
        $reservation->extra_payment->update(['expires_at' => now()->subMinute()]);
        $identity = $reservation->voucher->identity;

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            sprintf($this->apiReservationUrl . '/%s/cancel', $reservation->id), [], $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieAccountReservationSuccessByProductConfig(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;
        $dateNow = now();

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()->first();
        /** @var Product $product */
        $product = $provider->products()->first();

        $provider->update([
            'reservation_allow_extra_payments' => false,
        ]);

        $product->update([
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_YES,
        ]);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->apiReservationUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['checkout_url']);

        /** @var ProductReservation $reservation */
        $reservation = $voucher->product_reservations()->first();
        $this->assertNotNull($reservation);
        $this->assertNotNull($reservation->extra_payment?->payment_id);

        $response = $this->postJson("/mollie/webhooks", [
            'id' => $reservation->extra_payment->payment_id
        ]);

        $response->assertSuccessful();

        $reservation->refresh();
        $this->assertTrue($reservation->extra_payment->isPaid());
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieAccountReservationFailNotAllowedByFund(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;
        $dateNow = now();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);

        $this->updateFundProvider($organization, $fund, $fundProvider, false);

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()->first();
        /** @var Product $product */
        $product = $provider->products()->first();

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->apiReservationUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertJsonValidationErrorFor('product_id');
        $response->assertJsonFragment(['errors' => ['product_id' => [trans('validation.product_reservation.not_enough_voucher_funds')]]]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieAccountReservationFailNotAllowedByProduct(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;
        $dateNow = now();

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $this->assertConnectionCreated($provider, $dateNow);

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()->first();
        /** @var Product $product */
        $product = $provider->products()->first();

        $product->update([
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_NO
        ]);

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->apiReservationUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertJsonValidationErrorFor('product_id');
        $response->assertJsonFragment(['errors' => ['product_id' => [trans('validation.product_reservation.not_enough_voucher_funds')]]]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMollieAccountReservationFailNoConnection(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()->first();
        /** @var Product $product */
        $product = $provider->products()->first();

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->apiReservationUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertJsonValidationErrorFor('product_id');
        $response->assertJsonFragment(['errors' => ['product_id' => [trans('validation.product_reservation.not_enough_voucher_funds')]]]);
    }

    /**
     * @return Organization
     */
    private function getOrganization(): Organization
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_provider_extra_payments' => true,
        ]);

        $this->makeTestFund($organization, [], [
            'allow_reservations' => true,
        ]);

        return $organization->refresh();
    }

    /**
     * @return Organization
     */
    private function getProviderOrganization(): Organization
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'reservations_budget_enabled' => true,
            'reservation_allow_extra_payments' => true,
        ]);

        return $organization->refresh();
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $productData
     * @return Product
     */
    private function createProductForReservation(
        Organization $organization,
        Fund $fund,
        array $productData = []
    ): Product {
        /** @var Product $product */
        $product = Product::create([
            'name'                          => $this->faker->text(60),
            'description'                   => $this->faker->text(),
            'organization_id'               => $organization->id,
            'product_category_id'           => ProductCategory::first()->id,
            'reservation_enabled'           => 1,
            'expire_at'                     => now()->addDays(30),
            'price_type'                    => Product::PRICE_TYPE_REGULAR,
            'unlimited_stock'               => 1,
            'price_discount'                => 0,
            'total_amount'                  => 0,
            'sold_out'                      => 0,
            'price'                         => 20,
            'reservation_extra_payments'    => Product::RESERVATION_EXTRA_PAYMENT_GLOBAL,
            ...$productData,
        ]);

        $product->fund_providers()->firstOrCreate([
            'organization_id' => $organization->id,
            'fund_id'         => $fund->id,
            'state'           => FundProvider::STATE_ACCEPTED,
            'allow_budget'    => true,
            'allow_products'  => true,
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection|FundProvider[] $fund_providers */
        $fund_providers = FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(), [$fund->id]
        )->get();

        foreach ($fund_providers as $fund_provider) {
            $product->fund_provider_products()->create([
                'amount' => $product->price,
                'limit_total' => $product->unlimited_stock ? 1000 : $product->stock_amount,
                'fund_provider_id' => $fund_provider->id,
                'limit_per_identity' => $product->unlimited_stock ? 25 : ceil(max($product->stock_amount / 10, 1)),
            ]);
        }

        return $product;
    }
}
