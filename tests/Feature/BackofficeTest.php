<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesVoucherTransaction;
use Tests\Traits\TestsBackoffice;

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
     * Tests the eligibility check functionality for a fund.
     *
     * This method sets up a test environment, generates backoffice credentials,
     * creates a test fund and identity, and checks the eligibility of the identity
     * for the fund. It asserts that the identity is initially forbidden access,
     * becomes successful after setting a BSN record, and verifies the correct
     * backoffice log fields for both received and first use actions.
     *
     * @throws Exception
     * @return void
     */
    public function testEligibilityCheck(): void
    {
        // Generate test backoffice credentials, fund, identity and a product approved for the fund
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $identity = $this->makeIdentity();
        $product = $this->makeProviderAndProducts($fund)['approved'][0];

        // Set up backoffice responses with the generated credentials
        $this->setupBackofficeResponses($credentials);

        // Test the initial eligibility check, which should be forbidden since the identity has no BSN record
        $this->postJson("/api/v1/platform/funds/$fund->id/check", [], $this->makeApiHeaders($identity))->assertForbidden();

        // Set a BSN record for the identity
        $identity->setBsnRecord('123456789');

        // Test the eligibility check again, which should be successful
        $this->postJson("/api/v1/platform/funds/$fund->id/check", [], $this->makeApiHeaders($identity))->assertSuccessful();

        // Assert that there is one voucher for the identity (by backoffice)
        $this->assertCount(1, $identity->vouchers()->get());

        // Get the backoffice voucher for the identity
        $backofficeVoucher = $identity->vouchers[0];

        // Create a manual voucher for the fund and get its token without confirmation
        $manualVoucher = $fund->makeVoucher($identity);
        $manualVoucherToken = $manualVoucher->token_without_confirmation->address;

        // Assert that there are now two vouchers for the identity
        $this->assertCount(2, $identity->vouchers()->get());

        // Assert that "received" log is initially "pending" and changes to "success"
        $this->assertBackofficeReceivedLogPending($backofficeVoucher->backoffice_log_received()->first(), $identity->bsn);
        $this->artisan('funds.backoffice:send-logs');
        $this->assertBackofficeReceivedLogSuccess($backofficeVoucher->backoffice_log_received()->first(), $identity->bsn);

        // assert no "first use" log and create a reservation which should trigger "first use" event
        $this->assertNull($backofficeVoucher->backoffice_log_first_use()->first());
        $backofficeVoucher->reserveProduct($product);

        // Assert that "first use" log is initially "pending" and changes to "success"
        $this->assertBackofficeFirstUseLogPending($backofficeVoucher->backoffice_log_first_use()->first(), $identity->bsn);
        $this->artisan('funds.backoffice:send-logs');
        $this->assertBackofficeFirstUseLogSuccess($backofficeVoucher->backoffice_log_first_use()->first(), $identity->bsn);

        // Make a transaction with the manual voucher token (which should also trigger first use)
        $this->postJson("/api/v1/platform/provider/vouchers/$manualVoucherToken/transactions", [
            'amount' => 10,
            'organization_id' => $product->organization_id,
        ], $this->makeApiHeaders($product->organization->identity))->assertSuccessful();

        // Assert that "received" log is initially "pending" and changes to "success"
        $this->assertBackofficeFirstUseLogPending($manualVoucher->backoffice_log_first_use()->first(), $identity->bsn);
        $this->artisan('funds.backoffice:send-logs');
        $this->assertBackofficeFirstUseLogSuccess($manualVoucher->backoffice_log_first_use()->first(), $identity->bsn);
    }
}
