<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;

class FundProviderProductsTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use TestsReservations;
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesProductReservations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->throttleTotalPendingCount = Config::get('forus.reservations.throttle_total_pending');
        $this->throttleRecentlyCanceledCount = Config::get('forus.reservations.throttle_recently_canceled');

        Config::set('forus.reservations.throttle_total_pending', 4);
        Config::set('forus.reservations.throttle_recently_canceled', 2);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Config::set('forus.reservations.throttle_total_pending', $this->throttleTotalPendingCount);
        Config::set('forus.reservations.throttle_recently_canceled', $this->throttleRecentlyCanceledCount);
    }

    /**
     * @return void
     */
    public function testProviderProductVisibilityOnWebshop(): void
    {
        $fundProvider = $this->setupFundProvider();

        $this->assertProviderProductVisibilityOnWebshop($fundProvider);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testProviderVoucherVisibilityOnMeApp(): void
    {
        // assert visibility voucher, products and product vouchers on provider's me app
        $this->assertProviderVoucherVisibilityOnMeApp($this->setupFundProvider());
        $this->assertProviderVoucherProductsVisibilityOnMeApp($this->setupFundProvider());
        $this->assertProviderVoucherProductVouchersVisibilityOnMeApp($this->setupFundProvider());
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationLimitsOnWebshop(): void
    {
        $fundProvider = $this->setupFundProvider();

        // assert product visibility on webshop for limited products
        $this->assertReservationLimitsOnWebshop($fundProvider);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationsAreLimitedByVoucherBalanceOnWebshop(): void
    {
        $fundProvider = $this->setupFundProvider();

        // assert reservation is restricted to voucher's balance
        $this->assertReservationsAreLimitedByVoucherBalanceForBudgetProductsOnWebshop($fundProvider);
        $this->assertReservationsAreLimitedByVoucherBalanceForSubsidyProductsOnWebshop($fundProvider);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationsAreLimitedByExpirationDateOnWebshop(): void
    {
        $fundProvider = $this->setupFundProvider();

        // assert reservation is restricted to voucher's balance
        $this->assertReservationsAreLimitedByExpirationDateOnWebshop($fundProvider);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationTransactionAmount(): void
    {
        $fundProvider = $this->setupFundProvider();

        // assert reservation's voucher_transactions have correct values
        $this->assertReservationTransactionAmountForBudgetProduct($fundProvider);
        $this->assertReservationTransactionAmountForSubsidyProduct($fundProvider);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertProviderProductVisibilityOnWebshop(FundProvider $fundProvider): void
    {
        $implementation = $fundProvider->fund->getImplementation();
        $product = $this->apiMakeProduct($fundProvider->organization, [], $fundProvider->organization->identity);

        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: false);
        $this->assertWebshopProductsListVisibility($implementation, $product, false);

        $this->updateFundProvider($fundProvider, budget: true);
        $this->assertWebshopProductsListVisibility($implementation, $product, false);

        $this->updateFundProvider($fundProvider, products: true);
        $this->assertWebshopProductsListVisibility($implementation, $product, true);

        $this->updateFundProvider($fundProvider, products: false, enable: [$this->productData($product->id, 'budget')]);
        $this->assertWebshopProductsListVisibility($implementation, $product, true);

        $this->updateFundProvider($fundProvider, state: 'rejected');
        $this->assertWebshopProductsListVisibility($implementation, $product, false);

        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->assertWebshopProductsListVisibility($implementation, $product, true);

        $this->updateFundProvider($fundProvider, disable: [$product->id]);
        $this->assertWebshopProductsListVisibility($implementation, $product, false);

        $this->apiDeleteProduct($fundProvider->organization, $product, $fundProvider->organization->identity);
    }

    /**
     * @return FundProvider
     */
    protected function setupFundProvider(): FundProvider
    {
        // make test organizations
        $organizationSponsor = $this->apiMakeOrganization([], $this->makeIdentity($this->makeUniqueEmail()));
        $organizationProvider = $this->apiMakeOrganization([], $this->makeIdentity($this->makeUniqueEmail()));

        // make a test product, implementation and fund
        $fund = $this->apiMakeFund($organizationSponsor, [], $organizationSponsor->identity);

        // setup fund and assign the implementation
        $this->activateFundAndSetImplementation($fund, Implementation::create([
            'name' => $this->faker->text(20),
            'key' => $this->faker->uuid(),
        ]));

        // apply provider to the fund
        return $this->apiApplyProviderToFund($organizationProvider, $fund, $organizationProvider->identity);
    }

    /**
     * @param FundProvider $fundProvider
     * @param float $amount
     * @return Voucher
     */
    protected function setupVoucher(FundProvider $fundProvider, float $amount = 10): Voucher
    {
        return $this->apiMakeVoucherAsSponsor($fundProvider->fund->organization, $fundProvider->fund, [
            'assign_by_type' => 'email',
            'email' => $this->makeIdentity($this->makeUniqueEmail())->email,
            'amount' => $amount,
        ], $fundProvider->fund->organization->identity);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertProviderVoucherVisibilityOnMeApp(FundProvider $fundProvider): void
    {
        $voucher = $this->setupVoucher($fundProvider);

        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: false);
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, false);

        $this->updateFundProvider($fundProvider, products: true);
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, false);

        $this->updateFundProvider($fundProvider, budget: true);
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, true);

        $this->updateFundProvider($fundProvider, products: false);
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, true);

        $this->updateFundProvider($fundProvider, state: 'rejected');
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, false);

        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->assertMeAppProviderVoucherAccess($voucher, $fundProvider->organization, true);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertProviderVoucherProductsVisibilityOnMeApp(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [], $fundProvider->organization->identity);

        $voucher = $this->apiMakeVoucherAsSponsor($fundProvider->fund->organization, $fundProvider->fund, [
            'assign_by_type' => 'email',
            'email' => $this->makeIdentity($this->makeUniqueEmail())->email,
        ], $fundProvider->fund->organization->identity);

        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: false);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, false);

        $this->updateFundProvider($fundProvider, budget: true);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, false);

        $this->updateFundProvider($fundProvider, products: true);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, true);

        $this->updateFundProvider($fundProvider, products: false);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, false);

        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget')]);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, true);

        $this->updateFundProvider($fundProvider, state: 'rejected');
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, false);

        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, true);

        $this->updateFundProvider($fundProvider, disable: [$product->id]);
        $this->assertMeAppProviderVoucherProductsAccess($voucher, $fundProvider->organization, $product, false);

        $this->apiDeleteProduct($fundProvider->organization, $product, $fundProvider->organization->identity);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertProviderVoucherProductVouchersVisibilityOnMeApp(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [], $fundProvider->organization->identity);

        $voucher = $this->apiMakeVoucherAsSponsor($fundProvider->fund->organization, $fundProvider->fund, [
            'assign_by_type' => 'email',
            'email' => $this->makeIdentity($this->makeUniqueEmail())->email,
        ], $fundProvider->fund->organization->identity);

        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: true);
        $reservation = $this->makeProductReservation($voucher, $product, assertSuccess: true);

        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: false, disable: [$product->id]);
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, false);

        $this->updateFundProvider($fundProvider, budget: true);
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, false);

        $this->updateFundProvider($fundProvider, budget: true, products: true);
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, true);

        $this->updateFundProvider($fundProvider, products: false, enable: [$this->productData($product->id, 'budget')]);
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, true);

        $this->updateFundProvider($fundProvider, state: 'rejected');
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, false);

        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, true);

        $this->updateFundProvider($fundProvider, budget: true, products: false, disable: [$product->id]);
        $this->assertMeAppProviderVoucherProductVouchersAccess($reservation, false);

        $this->cancelProductReservation($reservation);
        $this->apiDeleteProduct($fundProvider->organization, $product, $fundProvider->organization->identity);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationLimitsOnWebshop(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [], $fundProvider->organization->identity);

        $voucher = $this->apiMakeVoucherAsSponsor($fundProvider->fund->organization, $fundProvider->fund, [
            'assign_by_type' => 'email',
            'email' => $this->makeIdentity($this->makeUniqueEmail())->email,
        ], $fundProvider->fund->organization->identity);

        // assert product without limits
        $this->updateFundProvider($fundProvider, state: 'accepted', enable: [$this->productData($product->id, 'budget')]);

        $reservation1 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $reservation2 = $this->makeProductReservation($voucher, $product, assertSuccess: true);

        // assert total limits are respected
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget', 2)]);
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);
        $this->cancelProductReservation($reservation1);
        $this->cancelProductReservation($reservation2);

        // assert that new reservations are allowed only after 1h from 2 canceled reservations
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);
        $this->travelTo(now()->addHour()->addMinute());
        $this->makeProductReservation($voucher, $product, assertSuccess: true);

        // assert increasing total limits but keeping the identity limit at one still caps the limit at 1
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget', 2, 1)]);
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);

        // assert increasing limit_per_identity to 2 raises the cap to 2
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget', 2, 2)]);

        $reservation3 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);

        // assert canceling the latest reservation allows for a new one
        $this->cancelProductReservation($reservation3);
        $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);

        // assert that at least 3 new reservations can be made when limits are set to unlimited
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget', totalUnlimited: true, identityUnlimited: true)]);

        $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->makeProductReservation($voucher, $product, assertSuccess: true);

        // assert throttling reached at 4 pending reservations
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);
        $this->apiDeleteProduct($fundProvider->organization, $product, $fundProvider->organization->identity);
        $this->travelBack();
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationsAreLimitedByVoucherBalanceForBudgetProductsOnWebshop(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [
            'price' => 5,
        ], $fundProvider->organization->identity);

        $voucher = $this->setupVoucher($fundProvider);

        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
        $this->assertSame(currency_format(5), $product->price);

        // assert product can be reserved as long as the voucher has enough funds and then fail after the balance is exceeded
        $this->updateFundProvider($fundProvider, state: 'accepted', enable: [$this->productData($product->id, 'budget')]);

        // assert 4 reservations succeed since they are within the balance
        $reservation1 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 5, available: 5);
        $reservation2 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 10, available: 0);

        // assert product can no longer be reserver by the $voucher since the balance is exhausted
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);

        // assert both reservations can be canceled and the voucher balance is restored
        $this->cancelProductReservation($reservation1);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 5, available: 5);
        $this->cancelProductReservation($reservation2);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationsAreLimitedByVoucherBalanceForSubsidyProductsOnWebshop(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [
            'price' => 5,
        ], $fundProvider->organization->identity);

        $voucher = $this->setupVoucher($fundProvider);

        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
        $this->assertSame(currency_format(5), $product->price);

        // assert product can be reserved as long as the voucher has enough funds and then fail after the balance is exceeded
        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'subsidy', amount: 2.5)]);

        // assert 4 reservations succeed and the voucher balance is reduced
        $reservation1 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 2.5, available: 7.5);
        $reservation2 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 5, available: 5);
        $reservation3 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 7.5, available: 2.5);
        $reservation4 = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 10, available: 0);

        // assert product can no longer be reserver by the $voucher since the balance is exhausted
        $this->makeProductReservation($voucher, $product, assertSuccess: false, assertErrors: ['product_id']);

        // assert all 4 reservations can be canceled and the voucher balance is restored
        $this->cancelProductReservation($reservation1);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 7.5, available: 2.5);
        $this->cancelProductReservation($reservation2);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 5, available: 5);
        $this->cancelProductReservation($reservation3);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 2.5, available: 7.5);
        $this->cancelProductReservation($reservation4);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationsAreLimitedByExpirationDateOnWebshop(FundProvider $fundProvider): void
    {
        $product1 = $this->apiMakeProduct($fundProvider->organization, ['price' => 5], $fundProvider->organization->identity);
        $product2 = $this->apiMakeProduct($fundProvider->organization, ['price' => 5], $fundProvider->organization->identity);

        $voucher = $this->setupVoucher($fundProvider, 20);
        $expireAt = now()->addDays(7);
        $implementation = $fundProvider->fund->getImplementation();

        // assert voucher balance and products prices
        $this->assertVoucherBalance($voucher, amount: 20, spent: 0, available: 20);
        $this->assertSame(currency_format(5), $product1->price);
        $this->assertSame(currency_format(5), $product2->price);

        // assert product can be reserved as long as the voucher has enough funds and then fail after the balance is exceeded
        $this->updateFundProvider($fundProvider, state: 'accepted', budget: false, products: false);
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product1->id, 'budget')]);
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product2->id, 'subsidy', amount: 2.5)]);

        // assert product reservations succeed and the voucher balance is reduced
        $this->makeProductReservation($voucher, $product1, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 20, spent: 5, available: 15);
        $this->makeProductReservation($voucher, $product2, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 20, spent: 7.5, available: 12.5);

        // assert products are visible and reservable
        $this->assertWebshopProductsListVisibility($implementation, $product1, true);
        $this->assertWebshopProductsListVisibility($implementation, $product2, true);

        // set product expiration date in 7 days
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product1->id, 'budget', expireAt: $expireAt)]);
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product2->id, 'subsidy', amount: 2.5, expireAt: $expireAt)]);

        // move to the next day after the product's expiration date and execute expiration:check command
        $this->travelTo($expireAt->clone()->addDay());
        $this->artisan('forus.action.expiration:check');

        // assert the products are no longer visible or reservable
        $this->assertWebshopProductsListVisibility($implementation, $product1, false);
        $this->assertWebshopProductsListVisibility($implementation, $product2, false);
        $this->makeProductReservation($voucher, $product1, assertSuccess: false, assertErrors: ['product_id']);
        $this->makeProductReservation($voucher, $product2, assertSuccess: false, assertErrors: ['product_id']);

        // approve all provider's products
        $this->updateFundProvider($fundProvider, products: true);

        // assert the products are now available since all products are now available and the product
        $this->assertWebshopProductsListVisibility($implementation, $product1, true);
        $this->assertWebshopProductsListVisibility($implementation, $product2, true);
        $this->makeProductReservation($voucher, $product1, assertSuccess: true);
        $this->makeProductReservation($voucher, $product2, assertSuccess: true);

        // remove products approval
        $this->updateFundProvider($fundProvider, products: false);

        // assert the products are no longer visible or reservable
        $this->assertWebshopProductsListVisibility($implementation, $product1, false);
        $this->assertWebshopProductsListVisibility($implementation, $product2, false);
        $this->makeProductReservation($voucher, $product1, assertSuccess: false, assertErrors: ['product_id']);
        $this->makeProductReservation($voucher, $product2, assertSuccess: false, assertErrors: ['product_id']);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationTransactionAmountForBudgetProduct(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [
            'price' => 5,
        ], $fundProvider->organization->identity);

        $voucher = $this->setupVoucher($fundProvider);

        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
        $this->assertSame(currency_format(5), $product->price);

        // assert product can be reserved as long as the voucher has enough funds and then fail after the balance is exceeded
        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'budget')]);

        // assert 4 reservations succeed and the voucher balance is reduced
        $reservation = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 5, available: 5);

        $this->assertSame(currency_format(5), $reservation->amount);
        $this->assertSame(null, $reservation->amount_voucher);
    }

    /**
     * @param FundProvider $fundProvider
     * @return void
     */
    protected function assertReservationTransactionAmountForSubsidyProduct(FundProvider $fundProvider): void
    {
        $product = $this->apiMakeProduct($fundProvider->organization, [
            'price' => 5,
        ], $fundProvider->organization->identity);

        $voucher = $this->setupVoucher($fundProvider);

        $this->assertVoucherBalance($voucher, amount: 10, spent: 0, available: 10);
        $this->assertSame(currency_format(5), $product->price);

        // assert product can be reserved as long as the voucher has enough funds and then fail after the balance is exceeded
        $this->updateFundProvider($fundProvider, state: 'accepted');
        $this->updateFundProvider($fundProvider, enable: [$this->productData($product->id, 'subsidy', amount: 2.5)]);

        // assert 4 reservations succeed and the voucher balance is reduced
        $reservation = $this->makeProductReservation($voucher, $product, assertSuccess: true);
        $this->assertVoucherBalance($voucher, amount: 10, spent: 2.5, available: 7.5);

        $this->assertSame(currency_format(5), $reservation->amount);
        $this->assertSame(currency_format(2.5), $reservation->amount_voucher);
    }

    /**
     * @param Implementation $implementation
     * @param Product $product
     * @param bool $visible
     * @return void
     */
    protected function assertWebshopProductsListVisibility(
        Implementation $implementation,
        Product $product,
        bool $visible
    ): void {
        $response = $this
            ->getJson('/api/v1/platform/products?per_page=100', ['client-key' => $implementation->key])
            ->assertSuccessful();

        // Extract all returned IDs
        $ids = array_map(fn (array $item) => $item['id'], $response->json('data'));

        if ($visible) {
            $this->assertContains($product->id, $ids, "Expected product ID $product->id to be visible, but it was not.");
        } else {
            $this->assertNotContains($product->id, $ids, "Expected product ID $product->id to be hidden, but it was returned.");
        }
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param bool $accessible
     * @return void
     */
    protected function assertMeAppProviderVoucherAccess(Voucher $voucher, Organization $organization, bool $accessible): void
    {
        $response = $this->apiMeAppVoucherAsProviderRequest($voucher, $organization->identity);

        if ($accessible) {
            $response->assertJsonPath('data.address', $voucher->token_without_confirmation->address);
        } else {
            $response->assertForbidden();
        }
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param Product $product
     * @param bool $accessible
     * @return void
     */
    protected function assertMeAppProviderVoucherProductsAccess(
        Voucher $voucher,
        Organization $organization,
        Product $product,
        bool $accessible,
    ): void {
        $response = $this->apiMeAppVoucherProductsAsProviderRequest($voucher, $organization->identity);

        if ($accessible) {
            $response
                ->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $product->id);
        } else {
            $response->assertForbidden();
        }
    }

    /**
     * @param ProductReservation $reservation
     * @param bool $accessible
     * @return void
     */
    protected function assertMeAppProviderVoucherProductVouchersAccess(
        ProductReservation $reservation,
        bool $accessible,
    ): void {
        $response = $this->apiMeAppVoucherProductVouchersAsProviderRequest(
            $reservation->voucher,
            $reservation->product->organization,
            $reservation->product->organization->identity,
        );

        if ($accessible) {
            $response
                ->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.address', $reservation->product_voucher->token_without_confirmation->address);
        } else {
            $response
                ->assertSuccessful()
                ->assertJsonCount(0, 'data');
        }
    }

    /**
     * @param FundProvider $fundProvider
     * @param string|null $state
     * @param bool|null $budget
     * @param bool|null $products
     * @param array|null $enable
     * @param array|null $disable
     * @return void
     */
    protected function updateFundProvider(
        FundProvider $fundProvider,
        ?string $state = null,
        ?bool $budget = null,
        ?bool $products = null,
        ?array $enable = null,
        ?array $disable = null,
    ): void {
        $this->apiUpdateFundProvider($fundProvider->fund->organization, $fundProvider, [
            ...$state !== null ? ['state' => $state] : [],
            ...$budget !== null ? ['allow_budget' => $budget] : [],
            ...$products !== null ? ['allow_products' => $products] : [],
            ...$enable !== null ? ['enable_products' => $enable] : [],
            ...$disable !== null ? ['disable_products' => $disable] : [],
        ], $fundProvider->fund->organization->identity);
    }

    /**
     * @param Fund $fund
     * @param Implementation $implementation
     * @return void
     */
    protected function activateFundAndSetImplementation(Fund $fund, Implementation $implementation): void
    {
        $fund->update([
            'state' => 'active',
        ]);

        $fund->fund_config()->first()->forceFill([
            'is_configured' => true,
            'implementation_id' => $implementation->id,
        ])->save();

        $fund->getOrCreateTopUp()->transactions()->create([
            'amount' => '100000',
        ]);
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertSuccess
     * @param array|null $assertErrors
     * @return ProductReservation|null
     */
    protected function makeProductReservation(
        Voucher $voucher,
        Product $product,
        bool $assertSuccess,
        ?array $assertErrors = null,
    ): ?ProductReservation {
        $response = $this->apiMakeProductReservationRequest([
            'product_id' => $product->id,
            'voucher_id' => $voucher->id,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ], $voucher->identity);

        if ($assertSuccess) {
            return ProductReservation::findOrFail($response->assertSuccessful()->json('data.id'));
        }

        if (!$assertErrors) {
            $response->assertForbidden();
        } else {
            $response->assertJsonValidationErrors($assertErrors);
        }

        return null;
    }

    /**
     * @param ProductReservation $reservation
     * @return ProductReservation
     */
    protected function cancelProductReservation(ProductReservation $reservation): ProductReservation
    {
        return $this->apiCancelProductReservation($reservation, $reservation->voucher->identity);
    }

    /**
     * @param int $id
     * @param string|null $type
     * @param int|null $total
     * @param int|null $identity
     * @param float|null $amount
     * @param bool|null $totalUnlimited
     * @param bool|null $identityUnlimited
     * @param Carbon|null $expireAt
     * @return array
     */
    protected function productData(
        int $id,
        string $type = null,
        ?int $total = null,
        ?int $identity = null,
        ?float $amount = null,
        ?bool $totalUnlimited = null,
        ?bool $identityUnlimited = null,
        ?Carbon $expireAt = null,
    ): array {
        return [
            'id' => $id,
            'payment_type' => $type,
            ...$total !== null ? ['limit_total' => $total] : [],
            ...$identity !== null ? ['limit_per_identity' => $identity] : [],
            ...$amount !== null ? ['amount' => currency_format($amount)] : [],
            ...$totalUnlimited !== null ? ['limit_total_unlimited' => $totalUnlimited] : [],
            ...$identityUnlimited !== null ? ['limit_per_identity_unlimited' => $identityUnlimited] : [],
            ...$expireAt !== null ? ['expire_at' => $expireAt->format('Y-m-d')] : [],
        ];
    }

    /**
     * @param Voucher $voucher
     * @param string $amount
     * @param string $spent
     * @param string $available
     * @return void
     */
    protected function assertVoucherBalance(Voucher $voucher, string $amount, string $spent, string $available): void
    {
        $this->assertSame($voucher->amount, currency_format($amount));
        $this->assertSame($voucher->amount_spent, currency_format($spent));
        $this->assertSame($voucher->amount_available, currency_format($available));
    }
}
