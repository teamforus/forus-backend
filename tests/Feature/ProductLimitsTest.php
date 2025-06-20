<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Product;
use App\Models\Organization;
use App\Models\FundProvider;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;

class ProductLimitsTest extends TestCase
{
    use MakesTestFunds;
    use MakesApiRequests;
    use MakesTestVouchers;
    use TestsReservations;
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesProductReservations;

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithBudgetVoucher(): void
    {
        $identitySponsor = $this->makeIdentity($this->makeUniqueEmail());
        $identityProvider = $this->makeIdentity($this->makeUniqueEmail());
        $identityRequester = $this->makeIdentity($this->makeUniqueEmail());

        $organizationSponsor = $this->apiMakeOrganization([], $identitySponsor);
        $organizationProvider = $this->apiMakeOrganization([], $identityProvider);

        $product = $this->apiMakeProduct($organizationProvider, [], $identityProvider);

        $implementation = Implementation::create([
            'name' => $this->faker->text(20),
            'key' => $this->faker->uuid(),
        ]);

        $fund = $this->apiMakeFund($organizationSponsor, [], $identitySponsor);

        $this->activateFundAndSetImplementation($fund, $implementation);

        $fundProvider = $this->apiApplyProviderToFund($organizationProvider, $fund, $identityProvider);

        $voucher = $this->apiMakeVoucherAsSponsor($organizationSponsor, $fund, [
            'assign_by_type' => 'email',
            'email' => $identityRequester->email,
        ], $identitySponsor);

        $this->assertProductToggleAndVisibilityOnWebshop($organizationSponsor, $fundProvider, $implementation, $product);
        $this->assertProviderToggleAndVoucherVisibilityOnMeApp($organizationSponsor, $fundProvider, $voucher);
        // $this->assertProviderToggleAndVoucherProductVisibilityOnMeApp($organizationSponsor, $fundProvider, $voucher, $product);
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

        $fund->fund_config()->update([
            'is_configured' => true,
            'implementation_id' => $implementation->id,
        ]);

        $fund->getOrCreateTopUp()->transactions()->create([
            'amount' => '100000',
        ]);
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param Implementation $implementation
     * @param Product|false $product
     *
     * @return void
     */
    protected function assertProductToggleAndVisibilityOnWebshop(
        Organization $organization,
        FundProvider $fundProvider,
        Implementation $implementation,
        Product|false $product,
    ): void {
        $this->assertProductsList($implementation, false);

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => false,
            'enable_products' => [[ 'id' => $product->id, 'payment_type' => 'budget' ]],
        ], $organization->identity);

        $this->assertProductsList($implementation, $product);

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => true,
            'enable_products' => [],
        ], $organization->identity);

        $this->assertProductsList($implementation, $product);

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => false,
            'disable_products' => [$product->id],
        ], $organization->identity);

        $this->assertProductsList($implementation, false);
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param Voucher|false $voucher
     *
     * @return void
     */
    protected function assertProviderToggleAndVoucherVisibilityOnMeApp(
        Organization $organization,
        FundProvider $fundProvider,
        Voucher|false $voucher,
    ): void {
        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => false,
            'allow_products' => false,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertForbidden();

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => false,
            'allow_products' => true,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertForbidden();

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => true,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param Voucher $voucher
     * @param Product|false $product
     *
     * @return void
     */
    protected function assertProviderToggleAndVoucherProductVisibilityOnMeApp(
        Organization $organization,
        FundProvider $fundProvider,
        Voucher $voucher,
        Product|false $product,
    ): void {
        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => false,
            'allow_products' => false,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertForbidden();

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => false,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertForbidden();

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => true,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => false,
            'enable_products' => [],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertForbidden();

        $this->apiUpdateFundProvider($organization, $fundProvider, [
            'state' => 'accepted',
            'allow_budget' => true,
            'allow_products' => false,
            'enable_products' => [[ 'id' => $product->id, 'payment_type' => 'budget' ]],
        ], $organization->identity);

        $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $fundProvider->organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);
    }

    /**
     * @param Implementation $implementation
     * @param Product|false $product
     * @return void
     */
    protected function assertProductsList(Implementation $implementation, Product|false $product): void
    {
        $response = $this
            ->getJson('/api/v1/platform/products', ['client-key' => $implementation->key])
            ->assertSuccessful();

        if ($product) {
            $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $product->id);
        } else {
            $response->assertJsonCount(0, 'data')->assertJsonPath('data.0.id', null);
        }
    }
}
