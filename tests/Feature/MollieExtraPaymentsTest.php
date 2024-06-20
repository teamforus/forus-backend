<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class MollieExtraPaymentsTest extends TestCase
{
    use MakesTestOrganizations, MakesTestFunds, DatabaseTransactions, WithFaker;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s';

    /**
     * @var string
     */
    protected string $apiUrlCreate = '/api/v1/platform/organizations/%s/mollie-connection';

    /**
     * @var string
     */
    protected string $apiUrlConnect = '/api/v1/platform/organizations/%s/mollie-connection/connect';

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
     * @return void
     * @throws Throwable
     */
    public function testOnboardingAccount(): void
    {
        $provider = $this->prepareFundProvider()->organization;
        $connection = $this->createPendingMollieConnection($provider, false);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testConnectExistingAccount(): void
    {
        $provider = $this->prepareFundProvider()->organization;
        $connection = $this->createPendingMollieConnection($provider);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProvidersWithoutConnectionCantUseExtraPayments(): void
    {
        $provider = $this->prepareFundProvider()->organization;

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonIsArray('data');
        $response->assertJsonCount(0, 'data');

        $this->assertFalse(
            $provider->canReceiveExtraPayments(),
            "Failed to assert that providers without mollie connection can't receive extra payments.",
        );
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProvidersWithActiveConnectionButDisallowedExtraPayments(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $this->createPendingMollieConnection($provider);

        $response = $this->patchJson(sprintf($this->apiFundProviderUrl, $organization->id, $fund->id, $fundProvider->id), [
            'allow_extra_payments' => false
        ], $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)));

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => false]);
        $provider->refresh();

        $this->assertAccessToActiveMollieConnectionEndpoint($provider, true);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProvidersWithActiveConnectionAndAllowedExtraPayments(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $connection = $this->createPendingMollieConnection($provider);
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->patchJson(sprintf($this->apiFundProviderUrl, $organization->id, $fund->id, $fundProvider->id), [
            'allow_extra_payments' => true
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => true]);
        $provider->refresh();

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
        $this->assertAccessToActiveMollieConnectionEndpoint($provider, false);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testMollieAccountReservationSuccess(): void
    {
        $this->makeReservation();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testReservationRejectNotPaidAndNotExpired(): void
    {
        $reservation = $this->makeReservation(false);
        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertForbidden();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testReservationRejectNotPaidAndExpired(): void
    {
        $reservation = $this->makeReservation(false);
        $reservation->extra_payment->update(['expires_at' => now()->subMinute()]);
        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        $response = $this->postJson(
            sprintf($this->apiProviderReservationUrl . '/%s/reject', $provider->id, $reservation->id), [], $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
     */
    public function testMollieAccountReservationSuccessByProductConfig(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        /** @var Voucher $voucher */
        /** @var Product $product */
        $voucher = $fund->vouchers()->first();
        $product = $provider->products()->first();

        $provider->update([
            'reservation_allow_extra_payments' => false,
        ]);

        $product->update([
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_YES,
        ]);

        $connection = $this->createPendingMollieConnection($provider);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);

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
     * @throws Throwable
     */
    public function testMollieAccountReservationFailNotAllowedByFund(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $connection = $this->createPendingMollieConnection($provider);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);

        $this->enableFundProviderExtraPayments($organization, $fund, $fundProvider, false);

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
     * @throws Throwable
     */
    public function testMollieAccountReservationFailNotAllowedByProduct(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $connection = $this->createPendingMollieConnection($provider);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);

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

        $response->assertJsonFragment(['errors' => ['product_id' => [
            trans('validation.product_reservation.not_enough_voucher_funds'),
        ]]]);
    }

    /**
     * @param Organization $provider
     * @param bool $existingMollieAccount
     * @return MollieConnection
     */
    private function createPendingMollieConnection(
        Organization $provider,
        bool $existingMollieAccount = true,
    ): MollieConnection {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        if ($existingMollieAccount) {
            $response = $this->postJson(sprintf($this->apiUrlConnect, $provider->id), [], $apiHeaders);
        } else {
            $response = $this->postJson(sprintf($this->apiUrlCreate, $provider->id), [
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
            ], $apiHeaders);
        }

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $connection = $this->findMollieConnectionById($provider, $response->json('id'));

        static::assertNotNull($connection);
        $this->assertEquals(MollieConnection::STATE_PENDING, $connection->connection_state);

        return $connection;
    }

    /**
     * @return void
     * @throws Throwable
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
     * @return FundProvider
     * @throws Throwable
     */
    private function prepareFundProvider(): FundProvider
    {
        $organization = $this->getOrganization();

        /** @var Fund $fund */
        $fund = $organization->funds->first();
        $this->assertNotNull($fund);

        $voucher = $fund->makeVoucher($organization->identity_address, [
            'state' => Voucher::STATE_ACTIVE
        ], 100);

        $this->assertNotNull($voucher);

        $provider = $this->makeProviderOrganization();
        $product = $this->createProductForReservation($provider);
        $fundProvider = $this->createFundProvider($provider, $fund);

        $this->assertNotNull($product);
        $this->assertNotNull($fundProvider);
        $this->assertFalse($fundProvider->allow_extra_payments);

        $this->enableFundProviderExtraPayments($organization, $fund, $fundProvider);

        return $fundProvider;
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param bool $allowExtraPayments
     * @return void
     */
    private function enableFundProviderExtraPayments(
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
     * @param Organization $provider
     * @param MollieConnection $mollieConnection
     * @return void
     */
    private function assertConnectionActiveAndOnboarded(
        Organization $provider,
        MollieConnection $mollieConnection
    ): void {
        $this->assertEquals(MollieConnection::STATE_ACTIVE, $mollieConnection->connection_state);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection/fetch', $provider->id), $apiHeaders);

        $response->assertSuccessful();
        $provider->refresh();
        $mollieConnection->refresh();

        $this->assertEquals(MollieConnection::ONBOARDING_STATE_COMPLETED, $mollieConnection->onboarding_state);
        $this->assertTrue($provider->canReceiveExtraPayments());
    }

    /**
     * @param Organization $provider
     * @param bool $forbidden
     * @return void
     */
    private function assertAccessToActiveMollieConnectionEndpoint(
        Organization $provider,
        bool $forbidden,
    ): void {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiUrl . '/mollie-connection', $provider->id), $apiHeaders);

        if ($forbidden) {
            $response->assertForbidden();
        } else {
            $response->assertSuccessful();
        }
    }

    /**
     * @param MollieConnection $mollieConnection
     * @return void
     */
    private function activateMollieConnection(MollieConnection $mollieConnection): void
    {
        $code = token_generator()->generate(64);
        $response = $this->getJson("/mollie/callback?state=$mollieConnection->state_code&code=$code");

        $expectUrl = Implementation::general()->urlProviderDashboard(
            "/organizations/$mollieConnection->organization_id/payment-methods"
        );

        $response->assertRedirect($expectUrl);
        $mollieConnection->refresh();
    }

    /**
     * @param bool $payExtra
     * @return ProductReservation
     * @throws Throwable
     */
    private function makeReservation(bool $payExtra = true): ProductReservation
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson(sprintf($this->apiUrl . '/mollie-connection/connect', $provider->id), [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $connection = $this->createPendingMollieConnection($provider);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);

        /** @var Voucher $voucher */
        /** @var Product $product */
        $voucher = $fund->vouchers()->first();
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
    private function makeProviderOrganization(): Organization
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'reservations_budget_enabled' => true,
            'reservation_allow_extra_payments' => true,
        ]);

        return $organization->refresh();
    }

    /**
     * @param Organization $provider
     * @param int $id
     * @return MollieConnection|null
     */
    private function findMollieConnectionById(Organization $provider, int $id): MollieConnection | null
    {
        /** @var MollieConnection $connection */
        $connection = $provider->mollie_connections()->where('id', $id)->first();
        static::assertNotNull($connection);

        return $connection;
    }

    /**
     * @param Organization $providerOrganization
     * @return Product
     */
    private function createProductForReservation(Organization $providerOrganization): Product
    {
        return Product::create([
            'name' => $this->faker->text(60),
            'description' => $this->faker->text(),
            'organization_id' => $providerOrganization->id,
            'product_category_id' => ProductCategory::first()->id,
            'reservation_enabled' => 1,
            'expire_at' => now()->addDays(30),
            'price_type' => Product::PRICE_TYPE_REGULAR,
            'unlimited_stock' => 1,
            'price_discount' => 0,
            'total_amount' => 0,
            'sold_out' => 0,
            'price' => 120,
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_GLOBAL,
        ]);
    }

    /**
     * @param Organization $providerOrganization
     * @param Fund $fund
     * @return FundProvider
     */
    private function createFundProvider(Organization $providerOrganization, Fund $fund): FundProvider
    {
        return FundProvider::create([
            'state' => FundProvider::STATE_ACCEPTED,
            'fund_id' => $fund->id,
            'allow_budget' => true,
            'organization_id' => $providerOrganization->id,
            'allow_products' => true,
        ])->refresh();
    }
}
