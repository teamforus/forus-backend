<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\Forus\TestData\TestData;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesVoucherTransaction;
use Tests\Traits\TestsBackoffice;
use Throwable;

class BackofficeTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use TestsBackoffice;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundProviders;
    use MakesVoucherTransaction;

    /**
     * Tests the complete happy flow for a fund application.
     *
     * @throws Throwable
     * @return void
     */
    public function testCompleteFundApplicationHappyFlow(): void
    {
        // Generate test backoffice credentials, fund, identity and a product approved for the fund
        $credentials = self::generateTestBackofficeCredentials();
        $bsn = TestData::randomFakeBsn();

        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $fund2 = $this->makeAndSetupBackofficeTestFund('fund_002', $credentials);
        $identity = $this->makeIdentity();

        // Set up backoffice responses with the generated credentials
        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key, $fund2->fund_config->key],
            residentBsn: [$bsn],
            eligibleBsn: [$bsn],
        );

        // Test the initial eligibility check, which should be forbidden since the identity has no BSN record
        $this->makeFundCheckRequest($fund, $identity)->assertForbidden();
        $this->makeFundCheckRequest($fund2, $identity)->assertForbidden();

        // Set a BSN record for the identity
        $identity->setBsnRecord($bsn);

        // Test the eligibility check again, which should be successful
        $this->makeFundCheckRequest($fund, $identity)->assertSuccessful();
        $this->makeFundCheckRequest($fund2, $identity)->assertSuccessful();

        // Assert that there is one voucher for the identity (by backoffice)
        $this->assertCount(2, $identity->vouchers()->get());

        // Get the backoffice voucher for the identity
        $voucher1 = $identity->vouchers->where('fund_id', $fund->id)->first();
        $voucher2 = $identity->vouchers->where('fund_id', $fund2->id)->first();

        // Assert both voucher initially have a pending received log and after "send-logs" command they are updated to success
        $this->assertBackOfficeReceivedLogIsCreatedAndUpdateAfterVoucherIsAssigned([$voucher1, $voucher2]);

        // Assert that first voucher creates and submits first use log after product reservation
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProductReservation($voucher1, $bsn);

        // Assert that second voucher creates and submits first use log after a transaction be provider is created
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProviderTransaction($voucher2, $bsn);
    }

    /**
     * Tests the complete logs happy flow for vouchers created via voucher generator.
     *
     * @throws Exception
     * @return void
     */
    public function testCompleteVoucherGeneratorHappyFlowForExistingBsnIdentities(): void
    {
        // Generate test backoffice credentials, fund, identity and a product approved for the fund
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $identity = $this->makeIdentity();
        $bsn = TestData::randomFakeBsn();

        // Set up backoffice responses with the generated credentials
        $this->setupBackofficeResponses($credentials, fundKeys: [$fund->fund_config->key]);

        // Set identity bsn and assert that identity has no vouchers
        $identity->setBsnRecord($bsn);
        $this->assertCount(0, $identity->vouchers()->get());

        // First voucher - assigned by BSN to a user with known BSN
        $voucher1 = $this->makeSponsorVoucherRequest($fund->organization, [
            'bsn' => $identity->bsn,
            'amount' => 100,
            'fund_id' => $fund->id,
            'activate' => 1,
            'assign_by_type' => 'bsn',
        ])->assertSuccessful()->json('data.id');

        // Second voucher - assigned by BSN to a user with known BSN
        $voucher2 = $this->makeSponsorVoucherRequest($fund->organization, [
            'bsn' => $identity->bsn,
            'amount' => 100,
            'fund_id' => $fund->id,
            'activate' => 1,
            'assign_by_type' => 'bsn',
        ])->assertSuccessful()->json('data.id');

        $this->assertCount(2, $identity->vouchers()->get());

        $voucher1 = $identity->vouchers()->where('id', $voucher1)->first();
        $voucher2 = $identity->vouchers()->where('id', $voucher2)->first();

        // Assert both voucher initially have a pending received log and after "send-logs" command they are updated to success
        $this->assertBackOfficeReceivedLogIsCreatedAndUpdateAfterVoucherIsAssigned([$voucher1, $voucher2]);

        // Assert that first voucher creates and submits first use log after product reservation
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProductReservation($voucher1, $bsn);

        // Assert that second voucher creates and submits first use log after a transaction be provider is created
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProviderTransaction($voucher2, $bsn);
    }

    /**
     * Tests the complete voucher generator happy flow for assign type bsn.
     *
     * @return void
     */
    public function testCompleteVoucherGeneratorHappyFlowForAssignTypeBsn(): void
    {
        // Generate test backoffice credentials, fund, identity and a product approved for the fund
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $bsn = TestData::randomFakeBsn();
        $bsn2 = TestData::randomFakeBsn();
        $identity = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $identity2->setBsnRecord($bsn2);

        // Set up backoffice responses with the generated credentials
        $this->setupBackofficeResponses($credentials, fundKeys: [$fund->fund_config->key]);

        $voucher1 = $this->makeSponsorVoucherRequest($fund->organization, [
            'bsn' => $bsn,
            'amount' => 100,
            'fund_id' => $fund->id,
            'activate' => 1,
            'report_type' => 'relation',
            'assign_by_type' => 'bsn',
        ])->assertSuccessful()->json('data.id');

        $voucher2 = $this->makeSponsorVoucherRequest($fund->organization, [
            'bsn' => $bsn,
            'amount' => 100,
            'fund_id' => $fund->id,
            'activate' => 1,
            'report_type' => 'relation',
            'assign_by_type' => 'bsn',
        ])->assertSuccessful()->json('data.id');

        $voucher1 = Voucher::find($voucher1);
        $voucher2 = Voucher::find($voucher2);

        $this->assertNull($voucher1->identity);
        $this->assertNull($voucher2->identity);

        // Assert both voucher initially have a pending received log and after "send-logs" command they are updated to success
        $this->assertBackOfficeReceivedLogIsCreatedAndUpdateAfterVoucherIsAssigned([$voucher1, $voucher2], $bsn);

        $voucher1->assignToIdentity($identity)->refresh();
        $voucher2->assignToIdentity($identity2)->refresh();

        // Assert that first voucher creates and submits first use log after product reservation
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProductReservation($voucher1, $bsn);

        // Assert that second voucher creates and submits first use log after a transaction be provider is created
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProviderTransaction($voucher2, $bsn);
    }

    /**
     * Tests the complete voucher generator happy flow for claimed BSN identities.
     *
     * @return void
     */
    public function testCompleteVoucherGeneratorHappyFlowForClaimedBsnIdentities(): void
    {
        // Generate test backoffice credentials, fund, identity and a product approved for the fund
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $identity = $this->makeIdentity();
        $bsn = TestData::randomFakeBsn();

        // Set up backoffice responses with the generated credentials
        $this->setupBackofficeResponses($credentials, fundKeys: [$fund->fund_config->key]);

        // Set identity bsn and assert that identity has no vouchers
        $this->assertCount(0, $identity->vouchers()->get());

        // Third voucher - assigned by BSN for a identity which is not in the system
        $voucher = $this->makeSponsorVoucherRequest($fund->organization, [
            'bsn' => $bsn,
            'amount' => 100,
            'fund_id' => $fund->id,
            'activate' => 1,
            'assign_by_type' => 'bsn',
        ])->assertSuccessful()->json('data.id');

        $this->assertCount(0, $identity->vouchers()->get());

        $voucher = Voucher::find($voucher);

        // Assert voucher not assigned
        $this->assertNull($voucher->identity()->first());

        // Asert received log is not created
        $this->assertBackOfficeReceivedLogIsNotCreated($voucher);

        // Set identity bsn record and assign voucher by bsn relation
        $identity->setBsnRecord($bsn);
        Voucher::assignAvailableToIdentityByBsn($identity);

        $voucher->refresh();
        $identity->refresh();

        // Assert after voucher was assigned to identity by bsn a "received" log where created for the bsn
        $this->assertEquals($voucher->identity_id, $identity->id);
        $this->assertBackOfficeReceivedLogIsCreatedAndUpdateAfterVoucherIsAssigned([$voucher], $bsn);

        // Assert that first voucher creates and submits first use log after product reservation
        $this->assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProductReservation($voucher, $bsn);
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertBackOfficeReceivedLogIsNotCreated(Voucher $voucher): void
    {
        $this->assertNull($voucher->backoffice_log_received()->first());
    }

    /**
     * @param Voucher[] $vouchers
     * @param string|null $bsn
     * @return void
     */
    protected function assertBackOfficeReceivedLogIsCreatedAndUpdateAfterVoucherIsAssigned(array $vouchers, ?string $bsn = null): void
    {
        foreach ($vouchers as $voucher) {
            $this->assertBackofficeReceivedLogPending($voucher->backoffice_log_received()->first(), $bsn ?: $voucher->identity->bsn);
        }

        $this->artisan('funds.backoffice:send-logs');

        foreach ($vouchers as $voucher) {
            $this->assertBackofficeReceivedLogSuccess($voucher->backoffice_log_received()->first(), $bsn ?: $voucher->identity->bsn);
        }
    }

    /**
     * @param Voucher $voucher
     * @param string $bsn
     * @return void
     */
    protected function assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProductReservation(Voucher $voucher, string $bsn): void
    {
        $product = $this->makeProviderAndProducts($voucher->fund)['approved'][0];

        $this->assertNull($voucher->backoffice_log_first_use()->first());
        $this->makeProductReservationRequest($voucher, $product)->assertSuccessful();
        $this->assertVoucherFirstUseLogGoesFromPendingToSuccess($voucher, $bsn);
    }

    /**
     * @param Voucher $voucher
     * @param string $bsn
     * @return void
     */
    protected function assertBackOfficeFirstUseLogIsCreatedAndUpdateAfterProviderTransaction(Voucher $voucher, string $bsn): void
    {
        $product = $this->makeProviderAndProducts($voucher->fund)['approved'][0];

        $this->assertNull($voucher->backoffice_log_first_use()->first());
        $this->makeProviderVoucherTransactionRequest($voucher, $product->organization)->assertSuccessful();
        $this->assertVoucherFirstUseLogGoesFromPendingToSuccess($voucher, $bsn);
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return void
     */
    protected function assertVoucherReceivedLogGoesFromPendingToSuccess(Voucher $voucher, Identity $identity): void
    {
        // Assert that "first_use" log is initially "pending" and changes to "success"
        $this->assertBackofficeReceivedLogPending($voucher->backoffice_log_received()->first(), $identity->bsn);
        $this->artisan('funds.backoffice:send-logs');
        $this->assertBackofficeReceivedLogSuccess($voucher->backoffice_log_received()->first(), $identity->bsn);
    }

    /**
     * @param Voucher $voucher
     * @param string $bsn
     * @return void
     */
    protected function assertVoucherFirstUseLogGoesFromPendingToSuccess(Voucher $voucher, string $bsn): void
    {
        // Assert that "first_use" log is initially "pending" and changes to "success"
        $this->assertBackofficeFirstUseLogPending($voucher->backoffice_log_first_use()->first(), $bsn);
        $this->artisan('funds.backoffice:send-logs');
        $this->assertBackofficeFirstUseLogSuccess($voucher->backoffice_log_first_use()->first(), $bsn);
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return TestResponse
     */
    protected function makeFundCheckRequest(Fund $fund, Identity $identity): TestResponse
    {
        return $this->postJson("/api/v1/platform/funds/$fund->id/check", [], $this->makeApiHeaders($identity));
    }

    /**
     * @param Voucher $voucher
     * @param Organization $providerOrganization
     * @return TestResponse
     */
    protected function makeProviderVoucherTransactionRequest(Voucher $voucher, Organization $providerOrganization): TestResponse
    {
        $manualVoucherToken = $voucher->token_without_confirmation->address;

        return $this->postJson("/api/v1/platform/provider/vouchers/$manualVoucherToken/transactions", [
            'amount' => 1,
            'organization_id' => $providerOrganization->id,
        ], $this->makeApiHeaders($providerOrganization->identity));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @return TestResponse
     */
    protected function makeSponsorVoucherRequest(Organization $organization, array $data): TestResponse
    {
        return $this->postJson("/api/v1/platform/organizations/$organization->id/sponsor/vouchers", [
            ...$data,
        ], $this->makeApiHeaders($organization->identity));
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return TestResponse
     */
    protected function makeProductReservationRequest(Voucher $voucher, Product $product): TestResponse
    {
        return $this->postJson('/api/v1/platform/product-reservations', [
            'product_id' => $product->id,
            'voucher_id' => $voucher->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ], $this->makeApiHeaders($voucher->identity));
    }
}
