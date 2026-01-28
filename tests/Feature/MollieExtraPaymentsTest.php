<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;
use Throwable;

class MollieExtraPaymentsTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestProducts;
    use TestsReservations;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundProviders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testOnboardingAccount(): void
    {
        $provider = $this->prepareFundProvider()->organization;
        $connection = $this->createPendingMollieConnection($provider, false);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConnectExistingAccount(): void
    {
        $provider = $this->prepareFundProvider()->organization;
        $connection = $this->createPendingMollieConnection($provider);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersWithoutConnectionCantUseExtraPayments(): void
    {
        $provider = $this->prepareFundProvider()->organization;

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson("/api/v1/platform/organizations/$provider->id/mollie-connection", $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonIsArray('data');
        $response->assertJsonCount(0, 'data');

        $this->assertFalse(
            $provider->canReceiveExtraPayments(),
            "Failed to assert that providers without mollie connection can't receive extra payments.",
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersWithActiveConnectionButDisallowedExtraPayments(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $this->createPendingMollieConnection($provider);

        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fund->id/providers/$fundProvider->id",
            [ 'allow_extra_payments' => false ],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity))
        );

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => false]);
        $provider->refresh();

        $this->assertAccessToActiveMollieConnectionEndpoint($provider, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersWithActiveConnectionAndAllowedExtraPayments(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;
        $organization = $fund->organization;

        $connection = $this->createPendingMollieConnection($provider);
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fund->id/providers/$fundProvider->id",
            [ 'allow_extra_payments' => true ],
            $apiHeaders,
        );

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => true]);
        $provider->refresh();

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
        $this->assertAccessToActiveMollieConnectionEndpoint($provider, false);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExtraPaymentReservationSuccess(): void
    {
        $reservation = $this->makeReservation();
        $this->payExtraPayment($reservation);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExtraPaymentReservationEmptyVoucherSuccess(): void
    {
        $reservation = $this->makeReservation(true);
        $this->payExtraPayment($reservation);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExtraPaymentReservationWhileExtraIsPendingSuccess(): void
    {
        $reservation = $this->makeReservation(true);

        // second reservation of the same product before extra payment is paid
        $response = $this->makeReservationStoreRequest($reservation->voucher, $reservation->product);
        $response->assertUnprocessable();

        $this->payExtraPayment($reservation);

        // second reservation of the same product after the extra payment is paid
        $response = $this->makeReservationStoreRequest($reservation->voucher, $reservation->product);
        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExtraPaymentDoubleReservationForNonFullFails(): void
    {
        $reservation = $this->makeReservation();
        $this->payExtraPayment($reservation);

        $response = $this->makeReservationStoreRequest($reservation->voucher, $reservation->product);
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationRejectNotPaidAndNotExpired(): void
    {
        $reservation = $this->makeReservation();
        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        $response = $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/reject",
            [],
            $headers
        );

        $response->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationRejectNotPaidAndExpired(): void
    {
        $reservation = $this->makeReservation();
        $reservation->extra_payment->update(['expires_at' => now()->subMinute()]);
        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        $response = $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/reject",
            [],
            $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationRejectPaid(): void
    {
        $reservation = $this->makeReservation();
        $provider = $reservation->product->organization;

        $this->payExtraPayment($reservation);

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/reject",
            [],
            $headers
        );

        $response->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationRefundAndReject(): void
    {
        $reservation = $this->makeReservation();
        $provider = $reservation->product->organization;

        $this->payExtraPayment($reservation);

        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/extra-payments/refund",
            headers: $headers,
        );

        $response->assertSuccessful();
        $reservation->refresh();

        $this->assertTrue($reservation->extra_payment->isFullyRefunded());

        $response = $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/reject",
            [],
            $headers
        );

        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationCancelPaidByRequester(): void
    {
        $reservation = $this->makeReservation();

        $this->payExtraPayment($reservation);
        $this->makeReservationCancelRequest($reservation)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationCancelNotPaidAndNotExpiredByRequester(): void
    {
        $this->makeReservationCancelRequest($this->makeReservation())->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationCancelNotPaidAndExpiredByRequester(): void
    {
        $reservation = $this->makeReservation();
        $reservation->extra_payment->update(['expires_at' => now()->subMinute()]);

        $this->makeReservationCancelRequest($reservation)->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMollieAccountReservationSuccessByProductConfig(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;

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

        $response = $this->makeReservationStoreRequest($voucher, $product);

        $response->assertSuccessful();
        $response->assertJsonStructure(['checkout_url']);

        /** @var ProductReservation $reservation */
        $reservation = $voucher->product_reservations()->first();
        $this->assertNotNull($reservation);
        $this->assertNotNull($reservation->extra_payment?->payment_id);

        $response = $this->postJson('/mollie/webhooks', [
            'id' => $reservation->extra_payment->payment_id,
        ]);

        $response->assertSuccessful();

        $reservation->refresh();
        $this->assertTrue($reservation->extra_payment->isPaid());
    }

    /**
     * @throws Throwable
     * @return void
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

        $response = $this->makeReservationStoreRequest(
            $fund->vouchers()->first(),
            $provider->products()->first(),
        );

        $response->assertJsonValidationErrorFor('product_id');
        $response->assertJsonFragment(['errors' => ['product_id' => [trans('validation.product_reservation.not_enough_voucher_funds')]]]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMollieAccountReservationFailNotAllowedByProduct(): void
    {
        $fundProvider = $this->prepareFundProvider();
        $provider = $fundProvider->organization;
        $fund = $fundProvider->fund;

        $connection = $this->createPendingMollieConnection($provider);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);

        /** @var Product $product */
        $product = $provider->products()->first();

        $product->update([
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_NO,
        ]);

        $response = $this->makeReservationStoreRequest($fund->vouchers()->first(), $product);

        $response->assertJsonValidationErrorFor('product_id');

        $response->assertJsonFragment(['errors' => ['product_id' => [
            trans('validation.product_reservation.not_enough_voucher_funds'),
        ]]]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMollieAccountReservationFailNoConnection(): void
    {
        $fundProvider = $this->prepareFundProvider();

        /** @var Voucher $voucher */
        /** @var Product $product */
        $voucher = $fundProvider->fund->vouchers()->first();
        $product = $fundProvider->organization->products()->first();

        $response = $this->makeReservationStoreRequest($voucher, $product);

        $response->assertJsonValidationErrorFor('product_id');
        $response->assertJsonFragment(['errors' => ['product_id' => [trans('validation.product_reservation.not_enough_voucher_funds')]]]);
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
            $response = $this->postJson("/api/v1/platform/organizations/$provider->id/mollie-connection/connect", [], $apiHeaders);
        } else {
            $response = $this->postJson("/api/v1/platform/organizations/$provider->id/mollie-connection", [
                'name' => $this->faker->name(),
                'country_code' => $this->faker->countryCode(),
                'profile_name' => $this->faker->name(),
                'phone' => $this->faker->e164PhoneNumber(),
                'website' => $this->faker->url(),
                'email' => $this->faker->email(),
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'street' => $this->faker->streetName(),
                'city' => $this->faker->city(),
                'postcode' => $this->faker->postcode(),
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
     * @throws Throwable
     * @return FundProvider
     */
    private function prepareFundProvider(): FundProvider
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);

        $organization->update([
            'allow_provider_extra_payments' => true,
        ]);

        $fund->fund_config->update([
            'allow_reservations' => true,
        ]);

        $this->assertNotNull($this->makeTestVoucher($fund, $organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 100));

        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $product = $this->makeTestProductForReservation($provider);
        $fundProvider = $this->makeTestFundProvider($provider, $fund);

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
        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fund->id/providers/$fundProvider->id",
            [ 'allow_extra_payments' => $allowExtraPayments ],
            $apiHeaders,
        );

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
        $response = $this->getJson("/api/v1/platform/organizations/$provider->id/mollie-connection/fetch", $apiHeaders);

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
        $response = $this->getJson("/api/v1/platform/organizations/$provider->id/mollie-connection", $apiHeaders);

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
     * @param bool $emptyVoucher
     * @throws Throwable
     * @return ProductReservation
     */
    private function makeReservation(bool $emptyVoucher = false): ProductReservation
    {
        $fundProvider = $this->prepareFundProvider();
        $this->setupMollieConnection($fundProvider->organization);

        $amount = $emptyVoucher ? 0 : 5;
        $voucher = $this->makeTestVoucher($fundProvider->fund, $this->makeIdentity($this->makeUniqueEmail()), amount: $amount);
        $product = $this->makeTestProductForReservation($fundProvider->organization);

        $product->update(['price' => 10]);

        if ($emptyVoucher) {
            $response = $this->makeReservationStoreRequest($voucher, $product);
            $response->assertUnprocessable();

            $fundProvider->update([
                'allow_extra_payments' => true,
                'allow_extra_payments_full' => true,
            ]);
        }

        $response = $this->makeReservationStoreRequest($voucher, $product);
        $response->assertSuccessful();
        $response->assertJsonStructure(['checkout_url']);

        $reservation = ProductReservation::find($response->json('id'));
        $this->assertProductReservationWithPaymentIdCreate($reservation);

        return $reservation;
    }

    /**
     * @param ProductReservation $reservation
     * @return void
     */
    private function assertProductReservationWithPaymentIdCreate(
        ProductReservation $reservation,
    ): void {
        $this->assertNotNull($reservation);
        $this->assertNotNull($reservation->extra_payment?->payment_id);
    }

    /**
     * @param ProductReservation $reservation
     * @return void
     */
    private function payExtraPayment(ProductReservation $reservation): void
    {
        $response = $this->postJson('/mollie/webhooks', [
            'id' => $reservation->extra_payment->payment_id,
        ]);

        $response->assertSuccessful();

        $reservation->refresh();
        $this->assertTrue($reservation->extra_payment->isPaid());
    }

    /**
     * @param Organization $provider
     * @return void
     */
    private function setupMollieConnection(Organization $provider): void
    {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->postJson("/api/v1/platform/organizations/$provider->id/mollie-connection/connect", [], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $connection = $this->createPendingMollieConnection($provider);

        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider, $connection);
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
}
