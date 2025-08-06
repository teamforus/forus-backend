<?php

namespace Tests\Browser\Filters\Dashboards;

use App\Models\Implementation;
use Laravel\Dusk\Browser;
use Throwable;

class VoucherTransactionsSponsorDashboardSearchFilterTest extends VoucherTransactionsProviderDashboardSearchFilterTest
{
    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsDashboardSearch(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $transactionCount = 2;
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $transaction = $this->prepareTestingData($fund, $transactionCount);
        $this->removePossibleDuplicateByIds($transaction);

        $this->rollbackModels([], function () use ($implementation, $transaction, $transactionCount) {
            $this->browse(function (Browser $browser) use ($implementation, $transaction, $transactionCount) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list and assert search
                $this->goToTransactionsPage($browser);
                $this->assertTransactionsSearchIsWorking($browser, $transaction, $transactionCount, 'sponsor');

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund->organization->employees()->where('identity_address', '!=', $fund->organization->identity_address)->delete();
            $fund && $this->deleteFund($fund);
        });
    }
}
